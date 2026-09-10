"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { listMyEnrollments, type Enrollment } from "@/lib/enrollments";

const STATUS_STYLE: Record<Enrollment["status"], string> = {
  active: "bg-brand-accent/10 text-brand-accent-dark",
  expired: "bg-red-50 text-red-700",
  cancelled: "bg-surface text-ink-3 border border-border",
  completed: "bg-brand-primary-soft text-brand-primary",
};

export default function MyCoursesPage() {
  const [enrollments, setEnrollments] = useState<Enrollment[] | "loading">("loading");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listMyEnrollments()
      .then(setEnrollments)
      .catch(() => setError("Couldn't load your courses."));
  }, []);

  return (
    <div>
      <h1 className="mb-1 font-heading text-2xl font-semibold text-ink">My Courses</h1>
      <p className="mb-6 text-sm text-ink-3">Courses you&apos;re currently enrolled in.</p>

      {error && <p className="text-sm text-red-600">{error}</p>}

      {enrollments === "loading" && <p className="text-sm text-ink-3">Loading…</p>}

      {enrollments !== "loading" && enrollments.length === 0 && (
        <div className="rounded-xl border border-border bg-background p-8 text-center">
          <p className="text-sm text-ink-3">
            You&apos;re not enrolled in any courses yet. Contact your admin to get
            started.
          </p>
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        {enrollments !== "loading" &&
          enrollments.map((enrollment) => (
            <Link
              key={enrollment.id}
              href={enrollment.status === "active" ? `/learn/${enrollment.course.id}` : "#"}
              className={`block rounded-xl border border-border bg-background p-5 shadow-sm transition-shadow ${
                enrollment.status === "active" ? "hover:shadow-md" : "cursor-not-allowed opacity-75"
              }`}
            >
              <div className="mb-2 flex items-start justify-between gap-2">
                <h2 className="font-heading text-base font-semibold text-ink">
                  {enrollment.course.title}
                </h2>
                <span
                  className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLE[enrollment.status]}`}
                >
                  {enrollment.status}
                </span>
              </div>
              {enrollment.course.short_description && (
                <p className="mb-3 text-sm text-ink-2">{enrollment.course.short_description}</p>
              )}
              <p className="text-xs text-ink-3">
                {enrollment.expires_at
                  ? `Access until ${new Date(enrollment.expires_at).toLocaleDateString()}`
                  : "Lifetime access"}
              </p>
            </Link>
          ))}
      </div>
    </div>
  );
}
