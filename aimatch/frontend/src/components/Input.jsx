export default function Input({
  label,
  type = 'text',
  name,
  value,
  onChange,
  placeholder,
  required = false,
  error = '',
  className = '',
}) {
  return (
    <div className="mb-5">
      {label && (
        <label 
          className="block mb-2 text-sm font-semibold"
          style={{ color: "var(--text-secondary)" }}
        >
          {label} {required && <span style={{ color: "var(--accent)" }}>*</span>}
        </label>
      )}
      <input
        type={type}
        name={name}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        required={required}
        className={"w-full px-4 py-3 rounded-lg transition-all " + className}
        style={{
          backgroundColor: "var(--input)",
          color: "var(--text-primary)",
          border: "1px solid " + (error ? "var(--accent)" : "var(--input-border)"),
        }}
      />
      {error && (
        <p className="mt-2 text-sm" style={{ color: "var(--accent)" }}>
          {error}
        </p>
      )}
    </div>
  );
}
