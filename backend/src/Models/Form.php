<?php

namespace UpApp\Models;

class Form
{
    private string $id;
    private string $userId;
    private string $formType; // DUP, TUP, DOS
    private array $formData;
    private string $completionStatus; // draft, completed
    private ?string $title;
    private ?string $aiFeedback; // AI-generated feedback (stored after first generation)
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        string $id,
        string $userId,
        string $formType,
        array $formData,
        string $completionStatus = 'draft',
        ?string $title = null,
        ?string $aiFeedback = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->formType = $formType;
        $this->formData = $formData;
        $this->completionStatus = $completionStatus;
        $this->title = $title ?? $this->generateTitle($formType, $formData);
        $this->aiFeedback = $aiFeedback;
        $this->createdAt = $createdAt ?? date('Y-m-d\TH:i:s\Z');
        $this->updatedAt = $updatedAt ?? date('Y-m-d\TH:i:s\Z');
    }

    private function generateTitle(string $formType, array $formData): string
    {
        // Get first filled field based on form type
        $text = '';
        switch ($formType) {
            case 'TUP':
                $text = $formData['situation_description'] ?? $formData['observation'] ?? $formData['quote'] ?? '';
                break;
            case 'DUP':
                $text = $formData['what_someone_said'] ?? '';
                break;
            case 'DOS':
                $text = $formData['judgment'] ?? $formData['person'] ?? '';
                break;
            case 'OK10':
                // Combine "who" and "what" fields for OK10
                $who = $formData['who'] ?? '';
                $what = $formData['what'] ?? '';
                if (!empty($who) && !empty($what)) {
                    $text = $who . ' - ' . $what;
                } elseif (!empty($who)) {
                    $text = $who;
                } elseif (!empty($what)) {
                    $text = $what;
                }
                break;
        }

        if (empty($text)) {
            return $formType . ' - ' . date('Y-m-d H:i');
        }

        // Get first 5 words
        $words = preg_split('/\s+/', trim($text), 6);
        $title = implode(' ', array_slice($words, 0, 5));

        // Add ellipsis if there are more words
        if (count($words) > 5) {
            $title .= '...';
        }

        return $title;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getCompletionStatus(): string
    {
        return $this->completionStatus;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getAiFeedback(): ?string
    {
        return $this->aiFeedback;
    }

    public function setFormData(array $formData): void
    {
        $this->formData = $formData;
        $this->updatedAt = date('Y-m-d\TH:i:s\Z');
    }

    public function setCompletionStatus(string $status): void
    {
        $this->completionStatus = $status;
        $this->updatedAt = date('Y-m-d\TH:i:s\Z');
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = date('Y-m-d\TH:i:s\Z');
    }

    public function setAiFeedback(?string $feedback): void
    {
        $this->aiFeedback = $feedback;
        $this->updatedAt = date('Y-m-d\TH:i:s\Z');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'form_type' => $this->formType,
            'form_data' => $this->formData,
            'completion_status' => $this->completionStatus,
            'title' => $this->title,
            'ai_feedback' => $this->aiFeedback,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
