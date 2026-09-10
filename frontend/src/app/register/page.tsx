"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import { register } from "@/lib/auth";

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  function update(field: keyof typeof form) {
    return (e: React.ChangeEvent<HTMLInputElement>) =>
      setForm((f) => ({ ...f, [field]: e.target.value }));
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setErrors({});
    setSubmitting(true);

    try {
      await register(form);
      router.push("/dashboard");
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setErrors(err.errors);
      } else {
        setErrors({ email: ["Something went wrong. Please try again."] });
      }
    } finally {
      setSubmitting(false);
    }
  }

  const fieldError = (field: string) => errors[field]?.[0];

  return (
    <main className="flex min-h-screen items-center justify-center bg-surface px-4 py-12">
      <div className="w-full max-w-sm">
        <div className="mb-8 text-center">
          <span className="font-heading text-2xl font-bold text-brand-primary">
            Pro-LMS
          </span>
          <p className="mt-1 text-sm text-ink-3">Learning Management System</p>
        </div>

        <form
          onSubmit={handleSubmit}
          className="space-y-4 rounded-xl border border-border bg-background p-8 shadow-sm"
        >
          <h1 className="font-heading text-lg font-semibold text-ink">
            Create your account
          </h1>

          <Field
            id="name"
            label="Full name"
            value={form.name}
            onChange={update("name")}
            error={fieldError("name")}
            autoComplete="name"
          />
          <Field
            id="email"
            label="Email"
            type="email"
            value={form.email}
            onChange={update("email")}
            error={fieldError("email")}
            autoComplete="email"
          />
          <Field
            id="password"
            label="Password"
            type="password"
            value={form.password}
            onChange={update("password")}
            error={fieldError("password")}
            autoComplete="new-password"
          />
          <Field
            id="password_confirmation"
            label="Confirm password"
            type="password"
            value={form.password_confirmation}
            onChange={update("password_confirmation")}
            autoComplete="new-password"
          />

          <button
            type="submit"
            disabled={submitting}
            className="w-full rounded-md bg-brand-primary py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-primary-dark disabled:opacity-60"
          >
            {submitting ? "Creating account…" : "Create account"}
          </button>

          <p className="text-center text-sm text-ink-3">
            Already have an account?{" "}
            <Link href="/login" className="font-medium text-brand-primary hover:underline">
              Sign in
            </Link>
          </p>
        </form>
      </div>
    </main>
  );
}

function Field({
  id,
  label,
  type = "text",
  value,
  onChange,
  error,
  autoComplete,
}: {
  id: string;
  label: string;
  type?: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  error?: string;
  autoComplete?: string;
}) {
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-medium text-ink-2">
        {label}
      </label>
      <input
        id={id}
        name={id}
        type={type}
        required
        autoComplete={autoComplete}
        value={value}
        onChange={onChange}
        className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
      />
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}
