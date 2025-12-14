import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import Header from '../components/Header';
import Card from '../components/Card';
import Input from '../components/Input';
import Button from '../components/Button';
import Alert from '../components/Alert';

export default function ProfilePage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [profile, setProfile] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setIsLoading(true);
      const token = localStorage.getItem('accessToken');
      const response = await fetch('https://open.kiam.kr/api/users/me', {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to load profile');
      }

      const data = await response.json();
      setProfile(data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900">
        <Header />
        <main className="max-w-5xl mx-auto px-6 py-8">
          <Card className="p-8">
            <div className="flex flex-col items-center justify-center">
              <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
              <p className="mt-4 text-gray-400">Loading...</p>
            </div>
          </Card>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900">
      <Header />
      <main className="max-w-5xl mx-auto px-6 py-8">
        <Card className="p-8">
          <h1 className="text-3xl font-bold text-blue-400 mb-8">Profile</h1>
          {profile && (
            <div className="space-y-4">
              <div><strong>Email:</strong> {profile.email}</div>
              <div><strong>Name:</strong> {profile.name || '-'}</div>
              <div><strong>Type:</strong> {profile.user_type}</div>
              <Button onClick={() => navigate('/dashboard')} variant="primary">Back to Dashboard</Button>
            </div>
          )}
        </Card>
      </main>
    </div>
  );
}
