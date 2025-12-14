import { createContext, useState, useEffect } from 'react';
import { themes } from '../constants/themes';

export const ThemeContext = createContext();

export function ThemeProvider({ children }) {
  const [currentTheme, setCurrentTheme] = useState(() => {
    // Load from localStorage or default to 'dark'
    const savedTheme = localStorage.getItem('aimatch-theme');
    return savedTheme && themes[savedTheme] ? savedTheme : 'dark';
  });

  useEffect(() => {
    // Save to localStorage
    localStorage.setItem('aimatch-theme', currentTheme);
    
    // Apply theme class to body
    document.body.className = `theme-${currentTheme}`;
    
    // Apply CSS variables
    const theme = themes[currentTheme];
    Object.entries(theme.colors).forEach(([key, value]) => {
      // Convert camelCase to kebab-case
      const cssVar = key.replace(/([A-Z])/g, '-').toLowerCase();
      document.documentElement.style.setProperty(`--${cssVar}`, value);
    });
  }, [currentTheme]);

  const setTheme = (themeName) => {
    if (themes[themeName]) {
      setCurrentTheme(themeName);
    }
  };

  const value = {
    theme: currentTheme,
    setTheme,
    themes: themes,
    currentThemeColors: themes[currentTheme].colors,
  };

  return (
    <ThemeContext.Provider value={value}>
      {children}
    </ThemeContext.Provider>
  );
}
