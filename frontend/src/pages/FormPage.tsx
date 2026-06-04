/**
 * FormPage - Routes to the correct form component based on form type.
 */
import React from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import TUPForm from '../components/Forms/TUPForm';
import DUPForm from '../components/Forms/DUPForm';
import DOSForm from '../components/Forms/DOSForm';

export default function FormPage() {
  const { formType } = useParams<{ formType: string }>();
  const [searchParams] = useSearchParams();
  const formId = searchParams.get('id');

  // Route to the correct form component
  switch (formType?.toUpperCase()) {
    case 'TUP':
      return <TUPForm formId={formId} />;
    case 'DUP':
      return <DUPForm formId={formId} />;
    case 'DOS':
      return <DOSForm formId={formId} />;
    default:
      return (
        <div style={{ padding: '20px', textAlign: 'center' }}>
          <h1>Nieznany typ formularza</h1>
          <p>Typ formularza "{formType}" nie jest obsługiwany.</p>
          <p>Dostępne typy: TUP, DUP, DOS</p>
        </div>
      );
  }
}
