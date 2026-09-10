"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { fetchCurrentUser, logout, type User } from "@/lib/auth";

export default function InstructorLayout({ children }: LayoutProps<"/instructor">) {
  const router = useRouter();
  const [user, setUser] = useState<User | null | "loading">("loading");

  useEffect(() => {
    fetchCurrentUser().then((u) => {
      if (!u) {
        router.replace("/login");
      } else if (u.role !== "instructor") {
        router.replace("/dashboard");
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
    <div className="min-h-screen bg-surface">
      <header className="border-b border-border bg-background">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
          <Link href="/instructor" className="font-heading text-lg font-bold text-brand-primary">
            Pro-LMS
          </Link>
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
      <div className="mx-auto max-w-5xl px-6 py-8">{children}</div>
    </div>
  );
}
