import { useState } from 'react';

interface Item {
  id: string;
  name_pl: string;
}

interface CollapsibleListProps {
  title: string;
  groups: Record<string, Item[]>;
  selected: string[];
  onChange: (selected: string[]) => void;
}

export default function CollapsibleList({ title, groups, selected = [], onChange }: CollapsibleListProps) {
  const [isExpanded, setIsExpanded] = useState(false);

  // Ensure selected is an array
  const safeSelected = Array.isArray(selected) ? selected : [];

  const handleToggleItem = (itemId: string) => {
    const newSelected = safeSelected.includes(itemId)
      ? safeSelected.filter(id => id !== itemId)
      : [...safeSelected, itemId];
    onChange(newSelected);
  };

  return (
    <div style={{ marginBottom: '15px', border: '1px solid #ccc', borderRadius: '4px' }}>
      <button
        type="button"
        onClick={() => setIsExpanded(!isExpanded)}
        style={{
          width: '100%',
          padding: '12px',
          background: '#f5f5f5',
          border: 'none',
          borderRadius: '4px',
          cursor: 'pointer',
          textAlign: 'left',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          fontSize: '16px',
          fontWeight: 'bold',
        }}
      >
        <span>{title}</span>
        <span>{isExpanded ? '▼' : '▶'}</span>
      </button>

      {isExpanded && (
        <div style={{ padding: '15px' }}>
          {Object.entries(groups).map(([groupName, items]) => (
            <div key={groupName} style={{ marginBottom: '15px' }}>
              <h4 style={{ marginBottom: '8px', color: '#666' }}>{groupName}</h4>
              <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))',
                gap: '8px'
              }}>
                {items.map(item => (
                  <label
                    key={item.id}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      cursor: 'pointer',
                      padding: '4px',
                    }}
                  >
                    <input
                      type="checkbox"
                      checked={safeSelected.includes(item.id)}
                      onChange={() => handleToggleItem(item.id)}
                      style={{ marginRight: '6px' }}
                    />
                    <span>{item.name_pl}</span>
                  </label>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
