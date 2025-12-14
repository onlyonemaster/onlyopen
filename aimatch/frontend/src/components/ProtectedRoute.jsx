import { useAuth } from '../hooks/useAuth';
import { Navigate } from 'react-router-dom';

export const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, isLoading } = useAuth();
  
  if (isLoading) return <div style={{ padding: '20px' }}>로딩 중...</div>;
  if (!isAuthenticated) return <Navigate to="/login" replace />;
  return <>{children}</>;
};
