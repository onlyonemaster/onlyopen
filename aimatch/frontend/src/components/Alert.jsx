export default function Alert({ type = 'error', children, className = '' }) {
  const getVariantStyle = () => {
    const baseStyle = {
      border: '1px solid',
    };
    
    if (type === 'error') {
      return {
        ...baseStyle,
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        borderColor: 'var(--accent)',
        color: 'var(--accent)',
      };
    } else if (type === 'success') {
      return {
        ...baseStyle,
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderColor: 'var(--success)',
        color: 'var(--success)',
      };
    } else if (type === 'warning') {
      return {
        ...baseStyle,
        backgroundColor: 'rgba(234, 179, 8, 0.1)',
        borderColor: '#eab308',
        color: '#eab308',
      };
    } else if (type === 'info') {
      return {
        ...baseStyle,
        backgroundColor: 'var(--primary-light)',
        borderColor: 'var(--primary)',
        color: 'var(--primary)',
      };
    }
  };

  return (
    <div 
      className={"p-4 rounded-lg " + className}
      style={getVariantStyle()}
    >
      {children}
    </div>
  );
}
