/**
 * T-Table component (T-shaped layout for feelings and needs).
 */
import React, { useState, useEffect } from 'react';
import { getFeelings, getNeeds, FeelingsData, NeedsData, Feeling, Need } from '../../services/reference';
import CollapsibleList from '../Common/CollapsibleList';

interface TTableProps {
  leftTitle: string;
  rightTitle: string;
  leftFreetext?: string;
  onLeftFreetextChange: (value: string) => void;
  leftSelected?: string[];
  onLeftSelectedChange: (value: string[]) => void;
  rightFreetext?: string;
  onRightFreetextChange: (value: string) => void;
  rightSelected?: string[];
  onRightSelectedChange: (value: string[]) => void;
}

export default function TTable({
  leftTitle,
  rightTitle,
  leftFreetext,
  onLeftFreetextChange,
  leftSelected,
  onLeftSelectedChange,
  rightFreetext,
  onRightFreetextChange,
  rightSelected,
  onRightSelectedChange,
}: TTableProps) {
  const [feelings, setFeelings] = useState<FeelingsData>({ fulfilled: {}, unfulfilled: {} });
  const [needs, setNeeds] = useState<NeedsData>({});
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        const [feelingsData, needsData] = await Promise.all([
          getFeelings(),
          getNeeds()
        ]);
        setFeelings(feelingsData);
        setNeeds(needsData);
      } catch (err) {
        console.error('Failed to load reference data:', err);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, []);

  // Merge fulfilled and unfulfilled feelings for display
  const allFeelings = {
    ...feelings.fulfilled,
    ...feelings.unfulfilled,
  };

  if (loading) {
    return <div>Ładowanie...</div>;
  }

  return (
    <div style={{
      border: '2px solid #4a90e2',
      borderRadius: '8px',
      padding: '20px',
      marginBottom: '30px'
    }}>
      {/* Top bar (T horizontal part) */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '20px',
        borderBottom: '2px solid #4a90e2',
        paddingBottom: '15px',
        marginBottom: '20px'
      }}>
        <div style={{ textAlign: 'center' }}>
          <h3 style={{ color: '#4a90e2', margin: 0 }}>{leftTitle}</h3>
        </div>
        <div style={{ textAlign: 'center' }}>
          <h3 style={{ color: '#4a90e2', margin: 0 }}>{rightTitle}</h3>
        </div>
      </div>

      {/* Bottom section (T vertical parts) */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '20px'
      }}>
        {/* Left column - Feelings */}
        <div>
          <textarea
            value={leftFreetext || ''}
            onChange={(e) => onLeftFreetextChange(e.target.value)}
            placeholder="np. radosny, smutny..."
            rows={3}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '14px',
              marginBottom: '15px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />

          <CollapsibleList
            title="Wybierz z listy uczuć"
            groups={allFeelings}
            selected={leftSelected || []}
            onChange={onLeftSelectedChange}
          />
        </div>

        {/* Right column - Needs */}
        <div>
          <textarea
            value={rightFreetext || ''}
            onChange={(e) => onRightFreetextChange(e.target.value)}
            placeholder="np. autonomia, zrozumienie..."
            rows={3}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '14px',
              marginBottom: '15px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />

          <CollapsibleList
            title="Wybierz z listy potrzeb"
            groups={needs}
            selected={rightSelected || []}
            onChange={onRightSelectedChange}
          />
        </div>
      </div>
    </div>
  );
}
