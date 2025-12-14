import { useAuth } from "../hooks/useAuth";
import { useNavigate } from "react-router-dom";
import Header from "../components/Header";
import Card from "../components/Card";

export default function DashboardPage() {
  const { user } = useAuth();
  const navigate = useNavigate();

  return (
    <div 
      className="min-h-screen"
      style={{
        background: "linear-gradient(to bottom right, var(--bg-gradient-start), var(--bg-gradient-end))",
      }}
    >
      <Header />

      <main className="max-w-7xl mx-auto px-6 py-8">
        <div className="mb-8">
          <h1 
            className="text-3xl font-bold mb-2"
            style={{ color: "var(--text-primary)" }}
          >
            대시보드
          </h1>
          <p style={{ color: "var(--text-muted)" }}>
            AIMatch Pro에 오신 것을 환영합니다
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <Card className="p-6">
            <h2 
              className="text-xl font-bold mb-4"
              style={{ color: "var(--text-primary)" }}
            >
              사용자 정보
            </h2>
            <div className="space-y-3">
              <div>
                <span 
                  className="text-sm"
                  style={{ color: "var(--text-muted)" }}
                >
                  아이디
                </span>
                <p 
                  className="font-medium"
                  style={{ color: "var(--text-primary)" }}
                >
                  {user?.id}
                </p>
              </div>
              <div>
                <span 
                  className="text-sm"
                  style={{ color: "var(--text-muted)" }}
                >
                  이메일
                </span>
                <p 
                  className="font-medium"
                  style={{ color: "var(--text-primary)" }}
                >
                  {user?.email}
                </p>
              </div>
              <div>
                <span 
                  className="text-sm"
                  style={{ color: "var(--text-muted)" }}
                >
                  이름
                </span>
                <p 
                  className="font-medium"
                  style={{ color: "var(--text-primary)" }}
                >
                  {user?.name}
                </p>
              </div>
              <div>
                <span 
                  className="text-sm"
                  style={{ color: "var(--text-muted)" }}
                >
                  사용자 유형
                </span>
                <p 
                  className="font-medium"
                  style={{ color: "var(--text-primary)" }}
                >
                  {user?.user_type || "DEVELOPER"}
                </p>
              </div>
            </div>
            <button
              onClick={() => navigate("/profile")}
              className="mt-6 w-full px-4 py-2 rounded-lg font-semibold transition-colors"
              style={{
                backgroundColor: "var(--primary)",
                color: "#ffffff",
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = "var(--primary-hover)";
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = "var(--primary)";
              }}
            >
              프로필 수정
            </button>
          </Card>

          <Card className="p-6 md:col-span-2">
            <h3 
              className="text-xl font-bold mb-4"
              style={{ color: "var(--text-primary)" }}
            >
              환영합니다!
            </h3>
            <div 
              className="space-y-3"
              style={{ color: "var(--text-secondary)" }}
            >
              <p>AIMatch Pro는 AI 기반의 개발자-회사 중개 플랫폼입니다.</p>
              <p>이 플랫폼은 현재 개발 중이며, 더 많은 기능이 추가될 예정입니다.</p>
              <ul 
                className="list-disc list-inside space-y-2 mt-4"
                style={{ color: "var(--text-muted)" }}
              >
                <li>프로필 관리 및 업데이트</li>
                <li>프로젝트 매칭 시스템</li>
                <li>실시간 알림 및 메시징</li>
                <li>포트폴리오 공유</li>
              </ul>
            </div>
          </Card>
        </div>
      </main>
    </div>
  );
}
