import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import Card from "../components/Card";
import Input from "../components/Input";
import Button from "../components/Button";
import Alert from "../components/Alert";

export default function SignupPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [name, setName] = useState("");
  const [userType, setUserType] = useState("DEVELOPER");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [emailError, setEmailError] = useState("");
  const [passwordError, setPasswordError] = useState("");
  const { signup } = useAuth();
  const navigate = useNavigate();

  const getPasswordStrength = (pwd) => {
    let strength = 0;
    if (pwd.length >= 8) strength++;
    if (pwd.length >= 12) strength++;
    if (/[A-Z]/.test(pwd)) strength++;
    if (/[0-9]/.test(pwd)) strength++;
    if (/[^A-Za-z0-9]/.test(pwd)) strength++;
    return strength;
  };

  const passwordStrength = getPasswordStrength(password);
  const strengthLabel = ["약함", "약함", "중간", "중간", "강함", "매우 강함"];

  const validateEmail = (e) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    setEmail(e.target.value);
    setEmailError(emailRegex.test(e.target.value) ? "" : "유효한 이메일을 입력하세요");
  };

  const validatePassword = (e) => {
    const pwd = e.target.value;
    setPassword(pwd);
    if (pwd.length === 0) {
      setPasswordError("");
    } else if (pwd.length < 8) {
      setPasswordError("비밀번호는 최소 8자 이상이어야 합니다");
    } else {
      setPasswordError("");
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (emailError || !email) {
      setError("유효한 이메일을 입력하세요");
      return;
    }
    if (passwordError || !password) {
      setError("비밀번호 요구사항을 만족하세요");
      return;
    }
    if (!name) {
      setError("이름을 입력하세요");
      return;
    }

    setIsLoading(true);
    try {
      await signup(email, password, name, userType);
      setSuccess("회원가입이 완료되었습니다! " + email + "로 발송된 인증 메일을 확인해주세요.");

      setTimeout(() => {
        navigate("/login");
      }, 2000);
    } catch (err) {
      setError(err.message || "회원가입 실패");
    } finally {
      setIsLoading(false);
    }
  };

  const getStrengthColor = (index) => {
    if (index < passwordStrength) {
      if (passwordStrength <= 1) return "#ef4444";
      if (passwordStrength <= 3) return "#eab308";
      return "#10b981";
    }
    return "var(--input)";
  };

  return (
    <div 
      className="min-h-screen flex items-center justify-center p-4"
      style={{
        background: "linear-gradient(to bottom right, var(--bg-gradient-start), var(--bg-gradient-end))",
      }}
    >
      <Card className="w-full max-w-md p-8">
        <h1 
          className="text-3xl font-bold text-center mb-8"
          style={{ color: "var(--primary)" }}
        >
          회원가입
        </h1>

        <form onSubmit={handleSubmit}>
          <div className="mb-5">
            <label 
              className="block mb-2 text-sm font-semibold"
              style={{ color: "var(--text-secondary)" }}
            >
              이메일 <span style={{ color: "var(--accent)" }}>*</span>
            </label>
            <input
              type="email"
              value={email}
              onChange={validateEmail}
              placeholder="example@example.com"
              required
              className="w-full px-4 py-3 rounded-lg transition-all"
              style={{
                backgroundColor: "var(--input)",
                color: "var(--text-primary)",
                border: "1px solid " + (emailError ? "var(--accent)" : "var(--input-border)"),
              }}
            />
            {emailError && (
              <p className="mt-2 text-sm" style={{ color: "var(--accent)" }}>
                {emailError}
              </p>
            )}
          </div>

          <Input
            label="이름"
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="홍길동"
            required
          />

          <div className="mb-5">
            <label 
              className="block mb-2 text-sm font-semibold"
              style={{ color: "var(--text-secondary)" }}
            >
              비밀번호 <span style={{ color: "var(--accent)" }}>*</span>
            </label>
            <input
              type="password"
              value={password}
              onChange={validatePassword}
              placeholder="8자 이상의 비밀번호"
              required
              className="w-full px-4 py-3 rounded-lg transition-all"
              style={{
                backgroundColor: "var(--input)",
                color: "var(--text-primary)",
                border: "1px solid " + (passwordError ? "var(--accent)" : "var(--input-border)"),
              }}
            />
            {password && (
              <div className="flex items-center gap-2 mt-2">
                <div className="flex gap-1 flex-1">
                  {[...Array(5)].map((_, i) => (
                    <div
                      key={i}
                      className="h-1 flex-1 rounded"
                      style={{
                        backgroundColor: getStrengthColor(i),
                      }}
                    />
                  ))}
                </div>
                <span 
                  className="text-xs"
                  style={{ color: "var(--text-muted)" }}
                >
                  {strengthLabel[passwordStrength]}
                </span>
              </div>
            )}
            {passwordError && (
              <p className="mt-2 text-sm" style={{ color: "var(--accent)" }}>
                {passwordError}
              </p>
            )}
          </div>

          <div className="mb-5">
            <label 
              className="block mb-2 text-sm font-semibold"
              style={{ color: "var(--text-secondary)" }}
            >
              역할 선택 <span style={{ color: "var(--accent)" }}>*</span>
            </label>
            <select
              value={userType}
              onChange={(e) => setUserType(e.target.value)}
              className="w-full px-4 py-3 rounded-lg transition-all"
              style={{
                backgroundColor: "var(--input)",
                color: "var(--text-primary)",
                border: "1px solid var(--input-border)",
              }}
            >
              <option value="DEVELOPER">개발자</option>
              <option value="AI_CODER">AI 코더</option>
              <option value="COMPANY">회사</option>
              <option value="LEARNER">학습자</option>
            </select>
          </div>

          {error && (
            <Alert type="error" className="mb-4">
              {error}
            </Alert>
          )}

          {success && (
            <Alert type="success" className="mb-4">
              {success}
            </Alert>
          )}

          <Button
            type="submit"
            disabled={isLoading || emailError || passwordError}
            variant="primary"
            className="w-full"
          >
            {isLoading ? "회원가입 중..." : "회원가입"}
          </Button>
        </form>

        <p 
          className="text-center mt-6"
          style={{ color: "var(--text-muted)" }}
        >
          이미 계정이 있으신가요?{" "}
          <a 
            href="/login" 
            className="font-semibold hover:underline"
            style={{ color: "var(--primary)" }}
          >
            로그인
          </a>
        </p>
      </Card>
    </div>
  );
}
