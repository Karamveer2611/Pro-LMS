"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { fetchCurrentUser, logout, type User } from "@/lib/auth";

const ROLE_LABEL: Record<User["role"], string> = {
  admin: "Admin",
  instructor: "Instructor",
  learner: "Learner",
};

export default function DashboardPage() {
  const router = useRouter();
  const [user, setUser] = useState<User | null | "loading">("loading");

  useEffect(() => {
    fetchCurrentUser().then((u) => {
      if (!u) {
        router.replace("/login");
      } else {
        setUser(u);
      }
    });
  }, [router]);

  if (user === "loading" || user === null) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-surface">
        <p className="text-sm text-ink-3">Loading…</p>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-surface">
      <header className="border-b border-border bg-background">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
          <span className="font-heading text-lg font-bold text-brand-primary">
            Pro-LMS
          </span>
          <button
            onClick={async () => {
              await logout();
              router.replace("/login");
            }}
            className="text-sm font-medium text-ink-2 hover:text-brand-primary"
          >
            Sign out
          </button>
        </div>
      </header>

      <div className="mx-auto max-w-5xl px-6 py-10">
        <div className="rounded-xl border border-border bg-background p-6 shadow-sm">
          <p className="text-sm text-ink-3">Welcome back,</p>
          <h1 className="font-heading text-2xl font-semibold text-ink">{user.name}</h1>
          <span className="mt-2 inline-block rounded-full bg-brand-primary-soft px-3 py-1 text-xs font-semibold text-brand-primary">
            {ROLE_LABEL[user.role]}
          </span>
        </div>
      </div>
    </main>
  );
}
