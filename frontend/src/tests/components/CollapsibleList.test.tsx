import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import CollapsibleList from '../../components/Common/CollapsibleList';

const mockGroups = {
  'Czułość': [
    { id: 'współczucie', name_pl: 'współczucie' },
    { id: 'serdeczność', name_pl: 'serdeczność' },
  ],
  'Radość': [
    { id: 'entuzjazm', name_pl: 'entuzjazm' },
  ],
};

describe('CollapsibleList', () => {
  it('renders with title', () => {
    render(
      <CollapsibleList
        title="Uczucia zaspokojenia"
        groups={mockGroups}
        selected={[]}
        onChange={() => {}}
      />
    );
    expect(screen.getByText('Uczucia zaspokojenia')).toBeInTheDocument();
  });

  it('is collapsed by default', () => {
    render(
      <CollapsibleList
        title="Uczucia"
        groups={mockGroups}
        selected={[]}
        onChange={() => {}}
      />
    );
    expect(screen.queryByText('współczucie')).not.toBeInTheDocument();
  });

  it('expands when header is clicked', () => {
    render(
      <CollapsibleList
        title="Uczucia"
        groups={mockGroups}
        selected={[]}
        onChange={() => {}}
      />
    );
    fireEvent.click(screen.getByRole('button'));
    expect(screen.getByText('współczucie')).toBeInTheDocument();
    expect(screen.getByText('entuzjazm')).toBeInTheDocument();
  });

  it('collapses again when header is clicked twice', () => {
    render(
      <CollapsibleList
        title="Uczucia"
        groups={mockGroups}
        selected={[]}
        onChange={() => {}}
      />
    );
    const btn = screen.getByRole('button');
    fireEvent.click(btn);
    fireEvent.click(btn);
    expect(screen.queryByText('współczucie')).not.toBeInTheDocument();
  });

  it('calls onChange when item is selected', () => {
    const onChange = vi.fn();
    render(
      <CollapsibleList
        title="Uczucia"
        groups={mockGroups}
        selected={[]}
        onChange={onChange}
      />
    );
    fireEvent.click(screen.getByRole('button'));
    fireEvent.click(screen.getByLabelText('współczucie') || screen.getAllByRole('checkbox')[0]);
    expect(onChange).toHaveBeenCalledWith(['współczucie']);
  });

  it('shows checked items as selected', () => {
    render(
      <CollapsibleList
        title="Uczucia"
        groups={mockGroups}
        selected={['serdeczność']}
        onChange={() => {}}
      />
    );
    fireEvent.click(screen.getByRole('button'));
    const checkboxes = screen.getAllByRole('checkbox');
    const checked = checkboxes.filter((cb) => (cb as HTMLInputElement).checked);
    expect(checked).toHaveLength(1);
  });

  it('renders empty groups without crashing', () => {
    render(
      <CollapsibleList
        title="Empty"
        groups={{}}
        selected={[]}
        onChange={() => {}}
      />
    );
    fireEvent.click(screen.getByRole('button'));
    expect(screen.getByText('Empty')).toBeInTheDocument();
  });
});
