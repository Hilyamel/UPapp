<?php

namespace UpApp\Handlers;

use UpApp\Models\Form;
use UpApp\Repositories\FormRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

class FormHandler
{
    private FormRepository $formRepository;

    public function __construct()
    {
        $this->formRepository = new FormRepository();
    }

    public function create(Request $request, Response $response): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['form_type']) || !in_array($data['form_type'], ['DUP', 'TUP', 'DOS'])) {
            return $this->errorResponse($response, 'Invalid form type', 400);
        }

        $form = new Form(
            Uuid::uuid4()->toString(),
            $_SESSION['user_id'],
            $data['form_type'],
            $data['form_data'] ?? [],
            $data['completion_status'] ?? 'draft'
        );

        if (!$this->formRepository->create($form)) {
            return $this->errorResponse($response, 'Failed to create form', 500);
        }

        return $this->successResponse($response, $form->toArray(), 201);
    }

    public function list(Request $request, Response $response): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $forms = $this->formRepository->findByUserId($_SESSION['user_id']);
        $formsArray = array_map(fn($form) => $form->toArray(), $forms);

        return $this->successResponse($response, $formsArray);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $formId = $args['id'] ?? null;
        if (!$formId) {
            return $this->errorResponse($response, 'Form ID required', 400);
        }

        $form = $this->formRepository->findById($formId);
        if (!$form) {
            return $this->errorResponse($response, 'Form not found', 404);
        }

        if ($form->getUserId() !== $_SESSION['user_id']) {
            return $this->errorResponse($response, 'Unauthorized', 403);
        }

        return $this->successResponse($response, $form->toArray());
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $formId = $args['id'] ?? null;
        if (!$formId) {
            return $this->errorResponse($response, 'Form ID required', 400);
        }

        $form = $this->formRepository->findById($formId);
        if (!$form) {
            return $this->errorResponse($response, 'Form not found', 404);
        }

        if ($form->getUserId() !== $_SESSION['user_id']) {
            return $this->errorResponse($response, 'Unauthorized', 403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (isset($data['form_data'])) {
            $form->setFormData($data['form_data']);
        }

        if (isset($data['completion_status'])) {
            $form->setCompletionStatus($data['completion_status']);
        }

        if (isset($data['title'])) {
            $form->setTitle($data['title']);
        }

        if (!$this->formRepository->update($form)) {
            return $this->errorResponse($response, 'Failed to update form', 500);
        }

        return $this->successResponse($response, $form->toArray());
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $formId = $args['id'] ?? null;
        if (!$formId) {
            return $this->errorResponse($response, 'Form ID required', 400);
        }

        $form = $this->formRepository->findById($formId);
        if (!$form) {
            return $this->errorResponse($response, 'Form not found', 404);
        }

        if ($form->getUserId() !== $_SESSION['user_id']) {
            return $this->errorResponse($response, 'Unauthorized', 403);
        }

        if (!$this->formRepository->delete($formId)) {
            return $this->errorResponse($response, 'Failed to delete form', 500);
        }

        return $this->successResponse($response, ['message' => 'Form deleted successfully']);
    }

    public function getSummary(Request $request, Response $response, array $args): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $formId = $args['id'] ?? null;
        if (!$formId) {
            return $this->errorResponse($response, 'Form ID required', 400);
        }

        $form = $this->formRepository->findById($formId);
        if (!$form) {
            return $this->errorResponse($response, 'Form not found', 404);
        }

        if ($form->getUserId() !== $_SESSION['user_id']) {
            return $this->errorResponse($response, 'Unauthorized', 403);
        }

        $summary = $this->formatFormSummary($form);
        return $this->successResponse($response, $summary);
    }

    public function generateAIFeedback(Request $request, Response $response, array $args): Response
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $formId = $args['id'] ?? null;
        if (!$formId) {
            return $this->errorResponse($response, 'Form ID required', 400);
        }

        $form = $this->formRepository->findById($formId);
        if (!$form) {
            return $this->errorResponse($response, 'Form not found', 404);
        }

        if ($form->getUserId() !== $_SESSION['user_id']) {
            return $this->errorResponse($response, 'Unauthorized', 403);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $overwrite = $data['overwrite'] ?? false;

        // Check if feedback already exists
        $existingFeedback = $form->getAiFeedback();
        if ($existingFeedback && !$overwrite) {
            return $this->successResponse($response, [
                'status' => 'feedback_exists',
                'message' => 'Ten formularz ma już zapisany feedback AI. Czy chcesz go nadpisać nowym feedbackiem?',
                'existing_feedback' => $existingFeedback
            ]);
        }

        // Generate empathetic NVC-based feedback
        $feedback = $this->generateNVCFeedback($form);

        // Save feedback to form
        $form->setAiFeedback($feedback);
        if (!$this->formRepository->update($form)) {
            return $this->errorResponse($response, 'Failed to save AI feedback', 500);
        }

        return $this->successResponse($response, [
            'status' => 'success',
            'feedback' => $feedback
        ]);
    }

    private function formatFormSummary(Form $form): array
    {
        $formType = $form->getFormType();
        $formData = $form->getFormData();

        $summary = [
            'id' => $form->getId(),
            'form_type' => $formType,
            'completion_status' => $form->getCompletionStatus(),
            'ai_feedback' => $form->getAiFeedback(),
            'created_at' => $form->getCreatedAt(),
            'updated_at' => $form->getUpdatedAt(),
            'sections' => []
        ];

        switch ($formType) {
            case 'TUP':
                $summary['sections'] = $this->formatTUPSummary($formData);
                break;
            case 'DUP':
                $summary['sections'] = $this->formatDUPSummary($formData);
                break;
            case 'DOS':
                $summary['sections'] = $this->formatDOSSummary($formData);
                break;
        }

        return $summary;
    }

    private function formatTUPSummary(array $data): array
    {
        $sections = [];

        // Situation description
        if (!empty($data['situation_description'])) {
            $sections[] = [
                'title' => 'Opis sytuacji',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Opis sytuacji',
                        'value' => $data['situation_description']
                    ]
                ]
            ];
        }

        // Observation, Quote, Judgments
        $obsFields = [];
        if (!empty($data['observation'])) {
            $obsFields[] = [
                'label' => 'Obserwacja',
                'value' => $data['observation']
            ];
        }
        if (!empty($data['quote'])) {
            $obsFields[] = [
                'label' => 'Cytat',
                'value' => $data['quote']
            ];
        }
        if (!empty($data['judgments'])) {
            $obsFields[] = [
                'label' => 'Osądy',
                'value' => $data['judgments']
            ];
        }
        if (!empty($obsFields)) {
            $sections[] = [
                'title' => 'Obserwacja i kontekst',
                'layout' => 'single-column',
                'fields' => $obsFields
            ];
        }

        // Your Feelings and Needs (T-Table 1)
        $yourFeelingsFreetext = $data['your_feelings_freetext'] ?? '';
        $yourFeelingsSelected = $this->formatSelectedArray($data['your_feelings_selected'] ?? []);
        $yourNeedsFreetext = $data['your_needs_freetext'] ?? '';
        $yourNeedsSelected = $this->formatSelectedArray($data['your_needs_selected'] ?? []);

        if ($yourFeelingsFreetext || $yourFeelingsSelected || $yourNeedsFreetext || $yourNeedsSelected) {
            $columns = [];

            $feelingsText = [];
            if ($yourFeelingsFreetext) $feelingsText[] = $yourFeelingsFreetext;
            if ($yourFeelingsSelected) $feelingsText[] = "Wybrane: " . $yourFeelingsSelected;

            $needsText = [];
            if ($yourNeedsFreetext) $needsText[] = $yourNeedsFreetext;
            if ($yourNeedsSelected) $needsText[] = "Wybrane: " . $yourNeedsSelected;

            $columns[] = [
                'title' => 'Twoje Uczucia',
                'text' => !empty($feelingsText) ? implode("\n", $feelingsText) : '-'
            ];
            $columns[] = [
                'title' => 'Twoje Potrzeby',
                'text' => !empty($needsText) ? implode("\n", $needsText) : '-'
            ];

            $sections[] = [
                'title' => 'Twoje uczucia i potrzeby',
                'layout' => 'two-column',
                'columns' => $columns
            ];
        }

        // Connection scale before
        if (isset($data['connection_scale_before'])) {
            $sections[] = [
                'title' => 'Skala połączenia - PRZED',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Jak oceniasz jakość połączenia PRZED?',
                        'value' => $data['connection_scale_before'] . '/10'
                    ]
                ]
            ];
        }

        // Their Feelings and Needs (T-Table 2)
        $theirFeelingsFreetext = $data['their_feelings_freetext'] ?? '';
        $theirFeelingsSelected = $this->formatSelectedArray($data['their_feelings_selected'] ?? []);
        $theirNeedsFreetext = $data['their_needs_freetext'] ?? '';
        $theirNeedsSelected = $this->formatSelectedArray($data['their_needs_selected'] ?? []);

        if ($theirFeelingsFreetext || $theirFeelingsSelected || $theirNeedsFreetext || $theirNeedsSelected) {
            $columns = [];

            $feelingsText = [];
            if ($theirFeelingsFreetext) $feelingsText[] = $theirFeelingsFreetext;
            if ($theirFeelingsSelected) $feelingsText[] = "Wybrane: " . $theirFeelingsSelected;

            $needsText = [];
            if ($theirNeedsFreetext) $needsText[] = $theirNeedsFreetext;
            if ($theirNeedsSelected) $needsText[] = "Wybrane: " . $theirNeedsSelected;

            $columns[] = [
                'title' => 'Jego/Jej Uczucia',
                'text' => !empty($feelingsText) ? implode("\n", $feelingsText) : '-'
            ];
            $columns[] = [
                'title' => 'Jego/Jej Potrzeby',
                'text' => !empty($needsText) ? implode("\n", $needsText) : '-'
            ];

            $sections[] = [
                'title' => 'Jego/Jej uczucia i potrzeby',
                'layout' => 'two-column',
                'columns' => $columns
            ];
        }

        // Connection scale after
        if (isset($data['connection_scale_after'])) {
            $sections[] = [
                'title' => 'Skala połączenia - PO',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Jak oceniasz jakość połączenia PO?',
                        'value' => $data['connection_scale_after'] . '/10'
                    ]
                ]
            ];

            // Connection change
            if (isset($data['connection_scale_before']) && isset($data['connection_scale_after'])) {
                $change = $data['connection_scale_after'] - $data['connection_scale_before'];
                $changeText = $change > 0 ? '+' . $change : (string)$change;
                $sections[] = [
                    'title' => 'Zmiana połączenia',
                    'layout' => 'single-column',
                    'fields' => [
                        [
                            'label' => 'Zmiana',
                            'value' => $changeText
                        ]
                    ]
                ];
            }
        }

        // Request
        if (!empty($data['request'])) {
            $sections[] = [
                'title' => 'Prośba',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'O co konkretnie prosisz?',
                        'value' => $data['request']
                    ]
                ]
            ];
        }

        return $sections;
    }

    private function formatDUPSummary(array $data): array
    {
        $sections = [];

        // What someone said
        if (!empty($data['what_someone_said'])) {
            $sections[] = [
                'title' => 'Co ktoś powiedział lub zrobił',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Opis sytuacji',
                        'value' => $data['what_someone_said']
                    ]
                ]
            ];
        }

        // Fulfilled feelings
        $fulfilledSelected = $this->collectSelectedItems($data, 'fulfilled_feelings_selected');
        $fulfilledFreetext = $data['fulfilled_feelings_freetext'] ?? '';

        $fulfilledValue = [];
        if ($fulfilledSelected) $fulfilledValue[] = "Z listy: " . $fulfilledSelected;
        if ($fulfilledFreetext) $fulfilledValue[] = "Własne: " . $fulfilledFreetext;

        if (!empty($fulfilledValue)) {
            $sections[] = [
                'title' => 'Uczucia sygnalizujące zaspokojenie potrzeb',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Uczucia zaspokojenia',
                        'value' => implode("\n", $fulfilledValue)
                    ]
                ]
            ];
        }

        // Unfulfilled feelings
        $unfulfilledSelected = $this->collectSelectedItems($data, 'unfulfilled_feelings_selected');
        $unfulfilledFreetext = $data['unfulfilled_feelings_freetext'] ?? '';

        $unfulfilledValue = [];
        if ($unfulfilledSelected) $unfulfilledValue[] = "Z listy: " . $unfulfilledSelected;
        if ($unfulfilledFreetext) $unfulfilledValue[] = "Własne: " . $unfulfilledFreetext;

        if (!empty($unfulfilledValue)) {
            $sections[] = [
                'title' => 'Uczucia sygnalizujące niezaspokojenie potrzeb',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Uczucia niezaspokojenia',
                        'value' => implode("\n", $unfulfilledValue)
                    ]
                ]
            ];
        }

        // Needs
        $needsSelected = $this->collectSelectedItems($data, 'needs_selected');
        $needsFreetext = $data['needs_freetext'] ?? '';

        $needsValue = [];
        if ($needsSelected) $needsValue[] = "Z listy: " . $needsSelected;
        if ($needsFreetext) $needsValue[] = "Własne: " . $needsFreetext;

        if (!empty($needsValue)) {
            $sections[] = [
                'title' => 'Potrzeby',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Jakie potrzeby były spełnione lub niespełnione?',
                        'value' => implode("\n", $needsValue)
                    ]
                ]
            ];
        }

        return $sections;
    }

    private function formatDOSSummary(array $data): array
    {
        $sections = [];

        // Person and Judgment
        $personJudgmentFields = [];
        if (!empty($data['person'])) {
            $personJudgmentFields[] = [
                'label' => 'Kogo dotyczy ten osąd?',
                'value' => $data['person']
            ];
        }
        if (!empty($data['judgment'])) {
            $personJudgmentFields[] = [
                'label' => 'Jak brzmi ten osąd?',
                'value' => $data['judgment']
            ];
        }

        if (!empty($personJudgmentFields)) {
            $sections[] = [
                'title' => 'Osąd',
                'layout' => 'single-column',
                'fields' => $personJudgmentFields
            ];
        }

        // Feelings
        $feelingsSelected = $this->collectSelectedItems($data, 'feelings_selected');
        $feelingsFreetext = $data['feelings_freetext'] ?? '';

        $feelingsValue = [];
        if ($feelingsSelected) $feelingsValue[] = "Z listy: " . $feelingsSelected;
        if ($feelingsFreetext) $feelingsValue[] = "Własne: " . $feelingsFreetext;

        if (!empty($feelingsValue)) {
            $sections[] = [
                'title' => 'Uczucia',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'Jakie uczucia kryją się za tym osądem?',
                        'value' => implode("\n", $feelingsValue)
                    ]
                ]
            ];
        }

        // Needs
        $needsSelected = $this->collectSelectedItems($data, 'needs_selected');
        $needsFreetext = $data['needs_freetext'] ?? '';

        $needsValue = [];
        if ($needsSelected) $needsValue[] = "Z listy: " . $needsSelected;
        if ($needsFreetext) $needsValue[] = "Własne: " . $needsFreetext;

        if (!empty($needsValue)) {
            $sections[] = [
                'title' => 'Potrzeby',
                'layout' => 'single-column',
                'fields' => [
                    [
                        'label' => 'O jakich potrzebach informują mnie te osądy?',
                        'value' => implode("\n", $needsValue)
                    ]
                ]
            ];
        }

        return $sections;
    }

    private function collectSelectedItems(array $data, string $prefix): string
    {
        $items = [];
        foreach ($data as $key => $value) {
            if (strpos($key, $prefix) === 0 && !empty($value)) {
                if (is_array($value)) {
                    $items = array_merge($items, $value);
                } else {
                    $items[] = $value;
                }
            }
        }
        return !empty($items) ? implode(', ', $items) : '';
    }

    private function formatSelectedArray($value): string
    {
        if (empty($value)) {
            return '';
        }
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return (string)$value;
    }

    private function generateNVCFeedback(Form $form): string
    {
        $formType = $form->getFormType();
        $formData = $form->getFormData();

        // Check if form is complete
        $isComplete = $this->isFormComplete($formType, $formData);

        if ($isComplete) {
            return $this->generateCompleteFormResponse($formType, $formData);
        } else {
            return $this->generateEmpatheticQuestion($formType, $formData);
        }
    }

    private function isFormComplete(string $formType, array $formData): bool
    {
        switch ($formType) {
            case 'TUP':
                $hasYourFeelings = !empty($formData['your_feelings_freetext']) || !empty($formData['your_feelings_selected']);
                $hasYourNeeds = !empty($formData['your_needs_freetext']) || !empty($formData['your_needs_selected']);
                $hasObservation = !empty($formData['situation_description']) || !empty($formData['observation']) || !empty($formData['quote']);
                return $hasObservation && $hasYourFeelings && $hasYourNeeds;

            case 'DUP':
                return !empty($formData['what_someone_said'])
                    && $this->hasSelectedItems($formData, 'feeling')
                    && $this->hasSelectedItems($formData, 'need');

            case 'DOS':
                return !empty($formData['judgment'])
                    && $this->hasSelectedItems($formData, 'feeling')
                    && $this->hasSelectedItems($formData, 'need');

            default:
                return false;
        }
    }

    private function hasSelectedItems(array $data, string $prefix): bool
    {
        foreach ($data as $key => $value) {
            if (strpos($key, $prefix) === 0 && !empty($value)) {
                return true;
            }
        }
        return false;
    }

    private function generateCompleteFormResponse(string $formType, array $formData): string
    {
        // Get a brief context from the form
        $observation = $this->extractObservation($formType, $formData);
        $shortObs = $this->shortenText($observation, 8);

        $responses = [
            "Widzę głębię Twojej refleksji nad tym, co się wydarzyło. Nazwanie swoich uczuć i potrzeb w kontekście \"$shortObs\" wymaga odwagi i autentyczności. Ta świadomość to klucz do budowania prawdziwego połączenia – zarówno z sobą, jak i z drugą osobą. Dzięki tej jasności możesz teraz wybierać, jak chcesz reagować i co chcesz powiedzieć.",

            "Doceniam, jak starannie przeanalizowałaś/eś sytuację \"$shortObs\". Rozpoznanie własnych uczuć i potrzeb to fundament komunikacji, która buduje mosty zamiast murów. Widzę, że rozumiesz, co było dla Ciebie ważne w tej sytuacji – to cenna świadomość, która pomoże Ci wyrazić siebie w sposób, który będzie słyszalny dla drugiej strony.",

            "Twoja praca nad tym formularzem pokazuje głębokie zaangażowanie w zrozumienie siebie. W kontekście \"$shortObs\" udało Ci się dotrzeć do tego, co naprawdę czujesz i czego potrzebujesz. Ta jasność jest jak kompas – pomoże Ci nawigować w tej i podobnych sytuacjach z większą świadomością i spokojem.",

            "Gratulacje za szczegółową analizę. Spojrzenie na sytuację \"$shortObs\" przez pryzmat uczuć i potrzeb to proces, który wymaga czasu i skupienia. Twoja gotowość do tego głębszego poznania siebie jest inspirująca. Mając tę świadomość, możesz teraz wybrać, jak chcesz działać dalej – w sposób, który będzie autentyczny i konstruktywny."
        ];

        return $responses[array_rand($responses)];
    }

    private function generateEmpatheticQuestion(string $formType, array $formData): string
    {
        $observation = $this->extractObservation($formType, $formData);
        $missingElement = $this->identifyMissingElement($formType, $formData);

        switch ($missingElement) {
            case 'feelings':
                return $this->suggestFeeling($observation, $formData);

            case 'needs':
                return $this->suggestNeed($observation, $formData);

            case 'observation':
                return "Zastanawiam się, co dokładnie wydarzyło się w tej sytuacji? Czy mógłbyś/mogłabyś podzielić się konkretnymi faktami, które zaobserwowałaś/eś?";

            default:
                return "Dziękuję za podzielenie się swoimi przeżyciami. Zastanawiam się, co było dla Ciebie najważniejsze w tej sytuacji?";
        }
    }

    private function extractObservation(string $formType, array $formData): string
    {
        switch ($formType) {
            case 'TUP':
                return $formData['situation_description'] ?? $formData['observation'] ?? $formData['quote'] ?? 'tę sytuację';
            case 'DUP':
                return $formData['what_someone_said'] ?? 'tę sytuację';
            case 'DOS':
                return $formData['judgment'] ?? 'to, co opisałaś/eś';
            default:
                return 'tę sytuację';
        }
    }

    private function identifyMissingElement(string $formType, array $formData): string
    {
        if (!$this->hasSelectedItems($formData, 'feeling')) {
            return 'feelings';
        }
        if (!$this->hasSelectedItems($formData, 'need')) {
            return 'needs';
        }
        if (empty($formData['observation']) && empty($formData['situation']) && empty($formData['judgment'])) {
            return 'observation';
        }
        return 'feelings'; // Default to exploring feelings
    }

    private function suggestFeeling(string $observation, array $formData): string
    {
        // Shorten observation to 5-10 words
        $shortObs = $this->shortenText($observation, 10);

        // Suggest contextual feelings based on keywords
        $suggestedFeelings = [
            'yelling' => [
                'keywords' => ['nakrzyczał', 'krzyczał', 'krzyczy'],
                'feeling' => 'wstyd',
                'need' => 'szacunku i godności'
            ],
            'forgetting' => [
                'keywords' => ['zapomniał', 'zapomnieli', 'ignoruje'],
                'feeling' => 'smutek',
                'need' => 'bycia ważnym'
            ],
            'criticizing' => [
                'keywords' => ['krytykował', 'oceniał', 'osądził'],
                'feeling' => 'frustrację',
                'need' => 'szacunku'
            ],
            'ignoring' => [
                'keywords' => ['nie odpowiedział', 'milczy', 'unika'],
                'feeling' => 'niepokój',
                'need' => 'jasności'
            ],
        ];

        foreach ($suggestedFeelings as $category => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (stripos($observation, $keyword) !== false) {
                    return "Gdy " . $shortObs . ", zastanawiam się, czy czujesz " . $data['feeling'] . ", bo potrzebujesz " . $data['need'] . "?";
                }
            }
        }

        // Default empathetic question
        return "Gdy wspominasz o tym, co się wydarzyło, zastanawiam się, jakie uczucia to w Tobie budzi?";
    }

    private function suggestNeed(string $observation, array $formData): string
    {
        $shortObs = $this->shortenText($observation, 10);

        // Get mentioned feelings to suggest appropriate needs
        $feelings = $this->collectSelectedItems($formData, 'feeling');

        if (stripos($feelings, 'złość') !== false || stripos($feelings, 'frustracja') !== false) {
            return "Gdy " . $shortObs . " i czujesz złość, czy potrzebujesz szacunku i uznania?";
        }

        if (stripos($feelings, 'smutek') !== false) {
            return "Gdy " . $shortObs . " i czujesz smutek, zastanawiam się, czy potrzebujesz bliskości i zrozumienia?";
        }

        if (stripos($feelings, 'strach') !== false || stripos($feelings, 'lęk') !== false) {
            return "Gdy " . $shortObs . " i czujesz strach, czy potrzebujesz bezpieczeństwa?";
        }

        // Default
        return "Gdy wspominasz o " . $shortObs . ", zastanawiam się, jakie potrzeby są dla Ciebie teraz najważniejsze?";
    }

    private function shortenText(string $text, int $maxWords): string
    {
        $words = explode(' ', $text);
        if (count($words) <= $maxWords) {
            return $text;
        }
        return implode(' ', array_slice($words, 0, $maxWords)) . '...';
    }


    private function successResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
            'error' => null
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    private function errorResponse(Response $response, string $message, int $status = 400): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'FORM_ERROR',
                'message' => $message
            ]
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
