export default function Card({ children, className = '' }) {
  return (
    <div 
      className={"border rounded-xl shadow-lg " + className}
      style={{
        backgroundColor: "var(--card)",
        borderColor: "var(--border)",
      }}
    >
      {children}
    </div>
  );
}
