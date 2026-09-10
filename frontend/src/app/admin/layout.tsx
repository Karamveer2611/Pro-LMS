"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { fetchCurrentUser, logout, type User } from "@/lib/auth";

const NAV = [{ href: "/admin/courses", label: "Courses" }];

export default function AdminLayout({ children }: LayoutProps<"/admin">) {
  const router = useRouter();
  const pathname = usePathname();
  const [user, setUser] = useState<User | null | "loading">("loading");

  useEffect(() => {
    fetchCurrentUser().then((u) => {
      if (!u) {
        router.replace("/login");
      } else if (u.role !== "admin") {
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
    <div className="flex min-h-screen">
      <aside className="flex w-60 flex-none flex-col bg-admin-sidebar">
        <div className="flex h-16 items-center border-b border-admin-sidebar-border px-5">
          <span className="font-heading text-lg font-bold text-white">Pro-LMS</span>
        </div>

        <nav className="flex-1 space-y-1 p-3">
          {NAV.map((item) => {
            const active = pathname.startsWith(item.href);
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`block rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                  active
                    ? "bg-admin-sidebar-hover text-white"
                    : "text-admin-sidebar-text hover:bg-admin-sidebar-hover hover:text-white"
                }`}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className="border-t border-admin-sidebar-border p-3">
          <button
            onClick={async () => {
              await logout();
              router.replace("/login");
            }}
            className="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-admin-sidebar-text hover:bg-admin-sidebar-hover hover:text-white"
          >
            Sign out
          </button>
        </div>
      </aside>

      <div className="flex-1 bg-surface">
        <header className="flex h-16 items-center justify-between border-b border-border bg-background px-6">
          <div />
          <div className="flex items-center gap-3 text-sm">
            <span className="text-ink-2">{user.name}</span>
            <span className="rounded-full bg-brand-primary-soft px-2.5 py-0.5 text-xs font-semibold text-brand-primary">
              Admin
            </span>
          </div>
        </header>
        <div className="p-6">{children}</div>
      </div>
    </div>
  );
}
