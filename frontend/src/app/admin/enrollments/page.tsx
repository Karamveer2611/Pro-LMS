"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { cancelEnrollment, listAllEnrollments, type Enrollment } from "@/lib/enrollments";

const STATUS_STYLE: Record<Enrollment["status"], string> = {
  active: "bg-brand-accent/10 text-brand-accent-dark",
  expired: "bg-red-50 text-red-700",
  cancelled: "bg-surface text-ink-3 border border-border",
  completed: "bg-brand-primary-soft text-brand-primary",
};

export default function AdminEnrollmentsPage() {
  const [enrollments, setEnrollments] = useState<Enrollment[] | "loading">("loading");
  const [error, setError] = useState<string | null>(null);

  function reload() {
    listAllEnrollments()
      .then(setEnrollments)
      .catch(() => setError("Couldn't load enrollments."));
  }

  useEffect(reload, []);

  async function handleCancel(id: number) {
    await cancelEnrollment(id);
    reload();
  }

  return (
    <div>
      <div className="mb-5 flex items-center justify-between">
        <h1 className="font-heading text-xl font-semibold text-ink">Enrollments</h1>
        <Link
          href="/admin/enrollments/new"
          className="rounded-md bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark"
        >
          + Enroll Learner
        </Link>
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <div className="overflow-x-auto rounded-xl border border-border bg-background">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-border bg-surface text-xs uppercase tracking-wide text-ink-3">
            <tr>
              <th className="px-4 py-3">Learner</th>
              <th className="px-4 py-3">Course</th>
              <th className="px-4 py-3">Enrolled</th>
              <th className="px-4 py-3">Expires</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            {enrollments === "loading" && (
              <tr>
                <td className="px-4 py-6 text-ink-3" colSpan={6}>
                  Loading…
                </td>
              </tr>
            )}
            {enrollments !== "loading" && enrollments.length === 0 && (
              <tr>
                <td className="px-4 py-6 text-ink-3" colSpan={6}>
                  No enrollments yet.
                </td>
              </tr>
            )}
            {enrollments !== "loading" &&
              enrollments.map((e) => (
                <tr key={e.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3">
                    <div className="font-medium text-ink">{e.user.name}</div>
                    <div className="text-xs text-ink-3">{e.user.email}</div>
                  </td>
                  <td className="px-4 py-3 text-ink-2">{e.course.title}</td>
                  <td className="px-4 py-3 text-ink-2">
                    {new Date(e.enrolled_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3 text-ink-2">
                    {e.expires_at ? new Date(e.expires_at).toLocaleDateString() : "Lifetime"}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLE[e.status]}`}
                    >
                      {e.status}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {e.status === "active" && (
                      <button
                        onClick={() => handleCancel(e.id)}
                        className="text-sm font-medium text-red-600 hover:underline"
                      >
                        Cancel
                      </button>
                    )}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
