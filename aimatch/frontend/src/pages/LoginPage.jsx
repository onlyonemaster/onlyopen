import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import Card from "../components/Card";
import Input from "../components/Input";
import Button from "../components/Button";
import Alert from "../components/Alert";

export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setIsLoading(true);

    try {
      await login(email, password);
      navigate("/dashboard");
    } catch (err) {
      const errorMsg = err.message || "로그인 실패";

      if (errorMsg.includes("Email not verified")) {
        setError(
          "이메일 인증이 필요합니다. 이메일 수신함에서 인증 링크를 클릭해주세요."
        );
      } else {
        setError(errorMsg);
      }
    } finally {
      setIsLoading(false);
    }
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
          로그인
        </h1>

        <Alert type="info" className="mb-6">
          <div className="flex items-start gap-2">
            <span className="text-xl">💡</span>
            <div>
              <strong>팁:</strong> 회원가입 시 입력한 이메일로 인증 메일이 발송됩니다. 인증을 완료한 후 로그인할 수 있습니다.
            </div>
          </div>
        </Alert>

        <form onSubmit={handleSubmit}>
          <Input
            label="이메일"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="example@example.com"
            required
          />

          <Input
            label="비밀번호"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="비밀번호"
            required
          />

          {error && (
            <Alert type="error" className="mb-4">
              {error}
            </Alert>
          )}

          <Button
            type="submit"
            disabled={isLoading}
            variant="primary"
            className="w-full"
          >
            {isLoading ? "로그인 중..." : "로그인"}
          </Button>
        </form>

        <p 
          className="text-center mt-6"
          style={{ color: "var(--text-muted)" }}
        >
          계정이 없으신가요?{" "}
          <a 
            href="/signup" 
            className="font-semibold hover:underline"
            style={{ color: "var(--primary)" }}
          >
            회원가입
          </a>
        </p>
      </Card>
    </div>
  );
}
