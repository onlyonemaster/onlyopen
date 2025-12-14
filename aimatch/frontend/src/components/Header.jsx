import { useNavigate } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import Button from "./Button";
import ThemeSelector from "./ThemeSelector";

export default function Header() {
  const navigate = useNavigate();
  const { user, logout } = useAuth();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  return (
    <header 
      className="border-b px-6 py-4"
      style={{
        backgroundColor: "var(--header)",
        borderColor: "var(--border)",
      }}
    >
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        <h1 
          className="text-2xl font-bold"
          style={{ color: "var(--primary)" }}
        >
          AIMatch Pro
        </h1>
        <div className="flex items-center gap-4">
          <ThemeSelector />
          {user && (
            <>
              <span 
                className="hidden sm:inline"
                style={{ color: "var(--text-secondary)" }}
              >
                {user.email}
              </span>
              <Button onClick={() => navigate("/profile")} variant="primary">
                내 프로필
              </Button>
              <Button onClick={handleLogout} variant="secondary">
                로그아웃
              </Button>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
