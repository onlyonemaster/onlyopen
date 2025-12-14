import { useState, useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import Card from "../components/Card";
import Button from "../components/Button";

export default function VerifyEmailPage() {
  const [searchParams] = useSearchParams();
  const [status, setStatus] = useState("loading");
  const [message, setMessage] = useState("이메일 인증을 진행 중입니다...");
  const { verifyEmail } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    const verifyTokenFromUrl = async () => {
      try {
        const token = searchParams.get("token");

        if (!token) {
          setStatus("error");
          setMessage("인증 토큰이 없습니다. 이메일의 인증 링크를 다시 확인해주세요.");
          return;
        }

        await verifyEmail(token);
        setStatus("success");
        setMessage("이메일 인증이 완료되었습니다! 잠시 후 대시보드로 이동합니다...");

        setTimeout(() => {
          navigate("/dashboard");
        }, 2000);
      } catch (error) {
        setStatus("error");
        const errorMessage = error.message || "이메일 인증 중 오류가 발생했습니다";

        if (errorMessage.includes("expired")) {
          setMessage("인증 토큰이 만료되었습니다. 다시 회원가입해주세요.");
        } else if (errorMessage.includes("not found")) {
          setMessage("유효하지 않은 토큰입니다. 이메일의 인증 링크를 다시 확인해주세요.");
        } else {
          setMessage(errorMessage);
        }
      }
    };

    verifyTokenFromUrl();
  }, [searchParams, verifyEmail, navigate]);

  return (
    <div 
      className="min-h-screen flex items-center justify-center p-4"
      style={{
        background: "linear-gradient(to bottom right, var(--bg-gradient-start), var(--bg-gradient-end))",
      }}
    >
      <Card className="w-full max-w-md p-8 text-center">
        <h1 
          className="text-3xl font-bold mb-8"
          style={{ color: "var(--primary)" }}
        >
          이메일 인증
        </h1>

        {status === "loading" && (
          <div className="flex flex-col items-center">
            <div 
              className="w-16 h-16 border-4 border-t-transparent rounded-full animate-spin"
              style={{ borderColor: "var(--primary)" }}
            ></div>
            <p 
              className="mt-6 text-lg"
              style={{ color: "var(--text-secondary)" }}
            >
              {message}
            </p>
          </div>
        )}

        {status === "success" && (
          <div className="flex flex-col items-center">
            <div 
              className="w-20 h-20 rounded-full flex items-center justify-center mb-6"
              style={{ backgroundColor: "var(--success)" }}
            >
              <svg className="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <p 
              className="text-xl mb-4"
              style={{ color: "var(--text-secondary)" }}
            >
              {message}
            </p>
            <div 
              className="w-full rounded-lg p-4"
              style={{
                backgroundColor: "var(--primary-light)",
                border: "1px solid var(--success)",
                color: "var(--success)",
              }}
            >
              인증이 성공했습니다!
            </div>
          </div>
        )}

        {status === "error" && (
          <div className="flex flex-col items-center">
            <div 
              className="w-20 h-20 rounded-full flex items-center justify-center mb-6"
              style={{ backgroundColor: "var(--accent)" }}
            >
              <svg className="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <div 
              className="w-full rounded-lg p-4 mb-6"
              style={{
                backgroundColor: "var(--primary-light)",
                border: "1px solid var(--accent)",
                color: "var(--accent)",
              }}
            >
              {message}
            </div>
            <Button
              onClick={() => navigate("/signup")}
              variant="primary"
              className="w-full mb-4"
            >
              회원가입 페이지로 이동
            </Button>
            <p style={{ color: "var(--text-muted)" }}>
              문제가 계속되면{" "}
              <a 
                href="/login" 
                className="font-semibold hover:underline"
                style={{ color: "var(--primary)" }}
              >
                로그인
              </a>
              을 시도해보세요.
            </p>
          </div>
        )}
      </Card>
    </div>
  );
}
