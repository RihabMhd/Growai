import { useState, useEffect } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useForm } from "react-hook-form";
import { resetPassword } from "../services/authService";

export default function ResetPassword() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token");
  const email = searchParams.get("email");

  const { register, handleSubmit, watch, formState: { errors }, setValue } = useForm();
  const [status, setStatus] = useState({ type: "", message: "" });
  const [isLoading, setIsLoading] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    if (email) setValue("email", email);
    if (token) setValue("token", token);
  }, [email, token, setValue]);

  const password = watch("password");

  const onSubmit = async (data) => {
    setIsLoading(true);
    setStatus({ type: "", message: "" });
    try {
      const response = await resetPassword(data);
      setStatus({ type: "success", message: response.message || "Password successfully reset!" });
      setTimeout(() => {
        navigate("/");
      }, 3000);
    } catch (err) {
      setStatus({ 
        type: "error", 
        message: err.response?.data?.message || "Failed to reset password. The link might be expired." 
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (!token) {
    return (
      <div className="auth-wrapper">
        <div className="auth-card" style={{ textAlign: 'center' }}>
          <h1 className="auth-title" style={{ color: 'var(--danger)', marginBottom: '15px' }}>Invalid Link</h1>
          <p className="auth-subtitle" style={{ marginBottom: '25px' }}>No reset token found in the URL. Please request a new link.</p>
          <Link to="/forgot-password" className="btn btn-primary">Request New Link</Link>
        </div>
      </div>
    );
  }

  return (
    <div className="auth-wrapper">
      <div className="auth-card">
        <div className="auth-header">
          <h1 className="auth-title">Create New Password</h1>
          <p className="auth-subtitle">Enter your new password below</p>
        </div>

        {status.message && (
          <div className={`alert ${status.type === "success" ? "alert-success" : "alert-error"}`}>
            {status.message}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)}>
          <input type="hidden" {...register("token")} />
          <input type="hidden" {...register("email")} />

          <div className="form-group">
            <label className="form-label">New Password</label>
            <input
              type="password"
              className="form-input"
              placeholder="••••••••"
              {...register("password", { 
                required: "Password is required",
                minLength: { value: 8, message: "Password must be at least 8 characters" }
              })}
            />
            {errors.password && <p className="form-error">{errors.password.message}</p>}
          </div>

          <div className="form-group">
            <label className="form-label">Confirm Password</label>
            <input
              type="password"
              className="form-input"
              placeholder="••••••••"
              {...register("password_confirmation", { 
                required: "Please confirm your password",
                validate: value => value === password || "Passwords do not match"
              })}
            />
            {errors.password_confirmation && <p className="form-error">{errors.password_confirmation.message}</p>}
          </div>

          <button type="submit" className="btn btn-primary" style={{ marginTop: '25px' }} disabled={isLoading || status.type === "success"}>
            {isLoading ? "Saving..." : "Reset Password"}
          </button>
        </form>
      </div>
    </div>
  );
}
