/**
 * 인증 API 서비스
 */

const API_BASE_URL = 'https://open.kiam.kr';

class AuthService {
  /**
   * 회원가입
   * @param {string} email - 이메일
   * @param {string} password - 비밀번호
   * @param {string} name - 이름
   * @param {string} userType - 사용자 유형
   * @returns {Promise<Object>} 회원가입 응답
   */
  static async signup(email, password, name, userType = 'DEVELOPER') {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/signup`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
          name,
          user_type: userType,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.detail || '회원가입 실패');
      }

      return data;
    } catch (error) {
      throw new Error(error.message || '회원가입 중 오류가 발생했습니다');
    }
  }

  /**
   * 로그인
   * @param {string} email - 이메일
   * @param {string} password - 비밀번호
   * @returns {Promise<Object>} 로그인 응답 (user, tokens)
   */
  static async login(email, password) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
        }),
      });

      let data;
      try {
        data = await response.json();
      } catch (parseError) {
        // HTML 응답일 경우
        const text = await response.text();
        if (text.includes('Internal Server Error')) {
          throw new Error('서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        }
        throw new Error('서버로부터 올바른 응답을 받지 못했습니다.');
      }

      if (!response.ok) {
        throw new Error(data.detail || '로그인 실패');
      }

      // 토큰 저장
      if (data.tokens && data.tokens.access_token) {
        localStorage.setItem('accessToken', data.tokens.access_token);
        localStorage.setItem('refreshToken', data.tokens.refresh_token);
      }

      return data;
    } catch (error) {
      throw new Error(error.message || '로그인 중 오류가 발생했습니다');
    }
  }

  /**
   * 이메일 인증
   * @param {string} token - 인증 토큰
   * @returns {Promise<Object>} 인증 응답 (user, tokens)
   */
  static async verifyEmail(token) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/verify-email?token=${encodeURIComponent(token)}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.detail || '이메일 인증 실패');
      }

      // 토큰 저장
      if (data.tokens && data.tokens.access_token) {
        localStorage.setItem('accessToken', data.tokens.access_token);
        localStorage.setItem('refreshToken', data.tokens.refresh_token);
      }

      return data;
    } catch (error) {
      throw new Error(error.message || '이메일 인증 중 오류가 발생했습니다');
    }
  }

  /**
   * 로그아웃
   */
  static logout() {
    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
  }

  /**
   * 현재 사용자 정보 조회
   * @returns {Promise<Object>} 사용자 정보
   */
  static async getCurrentUser() {
    try {
      const token = localStorage.getItem('accessToken');
      if (!token) {
        throw new Error('No token found');
      }

      const response = await fetch(`${API_BASE_URL}/auth/me`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.detail || '사용자 정보 조회 실패');
      }

      return data;
    } catch (error) {
      throw new Error(error.message || '사용자 정보 조회 중 오류가 발생했습니다');
    }
  }

  /**
   * 토큰 유효성 확인
   * @returns {boolean} 토큰 유효 여부
   */
  static hasValidToken() {
    return !!localStorage.getItem('accessToken');
  }

  /**
   * 토큰 가져오기
   * @returns {string|null} 액세스 토큰
   */
  static getToken() {
    return localStorage.getItem('accessToken');
  }
}

export default AuthService;
