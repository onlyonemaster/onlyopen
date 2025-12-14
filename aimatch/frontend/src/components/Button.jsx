export default function Button({
  children,
  onClick,
  variant = 'primary',
  type = 'button',
  disabled = false,
  className = '',
}) {
  const baseStyles = 'px-6 py-3 rounded-lg font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed';

  const getVariantStyle = () => {
    if (variant === 'primary') {
      return {
        backgroundColor: 'var(--primary)',
        color: '#ffffff',
      };
    } else if (variant === 'secondary') {
      return {
        backgroundColor: 'var(--bg-secondary)',
        color: 'var(--text-primary)',
      };
    } else if (variant === 'outline') {
      return {
        border: '2px solid var(--primary)',
        backgroundColor: 'transparent',
        color: 'var(--primary)',
      };
    }
  };

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={baseStyles + " " + className}
      style={getVariantStyle()}
      onMouseEnter={(e) => {
        if (!disabled) {
          if (variant === 'primary') {
            e.currentTarget.style.backgroundColor = 'var(--primary-hover)';
          } else if (variant === 'secondary') {
            e.currentTarget.style.backgroundColor = 'var(--input)';
          } else if (variant === 'outline') {
            e.currentTarget.style.backgroundColor = 'var(--primary)';
            e.currentTarget.style.color = '#ffffff';
          }
        }
      }}
      onMouseLeave={(e) => {
        if (!disabled) {
          const style = getVariantStyle();
          Object.assign(e.currentTarget.style, style);
        }
      }}
    >
      {children}
    </button>
  );
}
