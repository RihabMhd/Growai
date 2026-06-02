import { useEffect, useContext } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { AuthContext } from "../auth/AuthContext";

export default function AuthCallback() {
  const location = useLocation();
  const navigate = useNavigate();
  const { login } = useContext(AuthContext);

  useEffect(() => {
    const handleAuth = async () => {
      const params = new URLSearchParams(location.search);
      const token = params.get("token");

      if (token) {
        await login(token);
        navigate("/dashboard");
      } else {
        navigate("/");
      }
    };

    handleAuth();
  }, [location, login, navigate]);

  return (
    <div className="auth-wrapper">
      <div style={{ textAlign: 'center' }}>
        <div className="spinner"></div>
        <h2 style={{ fontSize: '1.25rem', fontWeight: '500', color: 'var(--text-main)', marginBottom: '10px' }}>Authenticating...</h2>
        <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Please wait while we connect your account</p>
      </div>
    </div>
  );
}