import { useState } from "react";
import { Link } from "react-router-dom";
import { useForm } from "react-hook-form";
import { forgotPassword } from "../services/authService";

export default function ForgotPassword() {
  const { register, handleSubmit, formState: { errors } } = useForm();
  const [status, setStatus] = useState({ type: "", message: "" });
  const [isLoading, setIsLoading] = useState(false);

  const onSubmit = async (data) => {
    setIsLoading(true);
    setStatus({ type: "", message: "" });
    try {
      const response = await forgotPassword(data.email);
      setStatus({ type: "success", message: response.message || "Password reset link sent to your email." });
    } catch (err) {
      setStatus({ 
        type: "error", 
        message: err.response?.data?.message || "Failed to send reset link. Please try again." 
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="auth-wrapper">
      <div className="auth-card">
        <div className="auth-header">
          <h1 className="auth-title">Reset Password</h1>
          <p className="auth-subtitle">Enter your email to receive a reset link</p>
        </div>

        {status.message && (
          <div className={`alert ${status.type === "success" ? "alert-success" : "alert-error"}`}>
            {status.message}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)}>
          <div className="form-group">
            <label className="form-label">Email</label>
            <input
              type="email"
              className="form-input"
              placeholder="you@agency.com"
              {...register("email", { required: "Email is required" })}
            />
            {errors.email && <p className="form-error">{errors.email.message}</p>}
          </div>

          <button type="submit" className="btn btn-primary" style={{ marginTop: '25px' }} disabled={isLoading}>
            {isLoading ? "Sending..." : "Send Reset Link"}
          </button>
        </form>

        <div style={{ marginTop: '25px', textAlign: 'center' }}>
          <Link to="/" className="text-link">
            &larr; Back to login
          </Link>
        </div>
      </div>
    </div>
  );
}
