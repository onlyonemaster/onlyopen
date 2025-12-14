import { useState, useRef, useEffect } from "react";
import { useTheme } from "../hooks/useTheme";
import { themeOrder } from "../constants/themes";

export default function ThemeSelector() {
  const { theme: currentTheme, setTheme, themes } = useTheme();
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const themeIcons = {
    light: "☀️",
    dark: "🌙",
    purple: "💜",
    gray: "⚪"
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors"
        style={{
          backgroundColor: "var(--input)",
          color: "var(--text-primary)",
          border: "1px solid var(--input-border)",
        }}
      >
        <span className="text-lg">{themeIcons[currentTheme]}</span>
        <span className="hidden sm:inline text-sm font-medium">
          {themes[currentTheme].name}
        </span>
        <svg
          className={"w-4 h-4 transition-transform " + (isOpen ? "rotate-180" : "")}
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {isOpen && (
        <div
          className="absolute right-0 mt-2 py-2 w-48 rounded-lg shadow-lg z-50"
          style={{
            backgroundColor: "var(--card)",
            border: "1px solid var(--border)",
          }}
        >
          {themeOrder.map((themeName) => (
            <button
              key={themeName}
              onClick={() => {
                setTheme(themeName);
                setIsOpen(false);
              }}
              className="w-full px-4 py-2 text-left flex items-center gap-3 transition-colors"
              style={{
                backgroundColor: currentTheme === themeName ? "var(--primary-light)" : "transparent",
                color: "var(--text-primary)",
              }}
              onMouseEnter={(e) => {
                if (currentTheme !== themeName) {
                  e.currentTarget.style.backgroundColor = "var(--bg-secondary)";
                }
              }}
              onMouseLeave={(e) => {
                if (currentTheme !== themeName) {
                  e.currentTarget.style.backgroundColor = "transparent";
                }
              }}
            >
              <span className="text-xl">{themeIcons[themeName]}</span>
              <span className="font-medium">{themes[themeName].name}</span>
              {currentTheme === themeName && (
                <span className="ml-auto text-sm">✓</span>
              )}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
