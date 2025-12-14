import { createContext, useContext, useState, useEffect } from 'react';
import AuthService from '../services/authService';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  // 초기 로드 시 토큰 확인
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        if (AuthService.hasValidToken()) {
          const userData = await AuthService.getCurrentUser();
          setUser(userData);
          setIsAuthenticated(true);
        }
      } catch (error) {
        // 토큰이 유효하지 않으면 초기화
        AuthService.logout();
        setUser(null);
        setIsAuthenticated(false);
      } finally {
        setIsLoading(false);
      }
    };

    initializeAuth();
  }, []);

  /**
   * 로그인
   * @param {string} email
   * @param {string} password
   * @throws {Error}
   */
  const login = async (email, password) => {
    try {
      const response = await AuthService.login(email, password);
      setUser(response.user);
      setIsAuthenticated(true);
      return response;
    } catch (error) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    }
  };

  /**
   * 회원가입
   * @param {string} email
   * @param {string} password
   * @param {string} name
   * @param {string} userType
   * @throws {Error}
   */
  const signup = async (email, password, name, userType = 'DEVELOPER') => {
    try {
      const response = await AuthService.signup(email, password, name, userType);
      // 회원가입 후에는 이메일 인증 대기 상태
      setUser(response.user);
      setIsAuthenticated(false); // 아직 인증되지 않음
      return response;
    } catch (error) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    }
  };

  /**
   * 이메일 인증
   * @param {string} token
   * @throws {Error}
   */
  const verifyEmail = async (token) => {
    try {
      const response = await AuthService.verifyEmail(token);
      setUser(response.user);
      setIsAuthenticated(true);
      return response;
    } catch (error) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    }
  };

  /**
   * 로그아웃
   */
  const logout = () => {
    AuthService.logout();
    setUser(null);
    setIsAuthenticated(false);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated,
        login,
        signup,
        verifyEmail,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};
