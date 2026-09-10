import Link from "next/link";

export default function HomePage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-surface px-4">
      <div className="text-center">
        <span className="font-heading text-3xl font-bold text-brand-primary">
          Pro-LMS
        </span>
        <p className="mt-2 text-ink-3">
          Learning Management System — under construction.
        </p>
        <Link
          href="/login"
          className="mt-6 inline-block rounded-md bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark"
        >
          Sign in
        </Link>
      </div>
    </main>
  );
}
