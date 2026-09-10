"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { listBatches, type Batch, type BatchStatus } from "@/lib/batches";
import { listAllCoursesForFilter } from "@/lib/batches";
import type { Course } from "@/lib/courses";

const STATUS_STYLE: Record<BatchStatus, string> = {
  upcoming: "bg-brand-primary-soft text-brand-primary",
  ongoing: "bg-brand-accent/10 text-brand-accent-dark",
  completed: "bg-surface text-ink-3 border border-border",
  cancelled: "bg-red-50 text-red-700",
};

export default function AdminBatchesPage() {
  const [batches, setBatches] = useState<Batch[] | "loading">("loading");
  const [courses, setCourses] = useState<Course[]>([]);
  const [statusFilter, setStatusFilter] = useState<BatchStatus | "all">("all");
  const [courseFilter, setCourseFilter] = useState<number | "all">("all");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listAllCoursesForFilter().then(setCourses).catch(() => {});
  }, []);

  useEffect(() => {
    setBatches("loading");
    listBatches({
      status: statusFilter === "all" ? undefined : statusFilter,
      course_id: courseFilter === "all" ? undefined : courseFilter,
    })
      .then(setBatches)
      .catch(() => setError("Couldn't load batches."));
  }, [statusFilter, courseFilter]);

  return (
    <div>
      <div className="mb-5 flex items-center justify-between">
        <h1 className="font-heading text-xl font-semibold text-ink">Batches</h1>
        <Link
          href="/admin/batches/new"
          className="rounded-md bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark"
        >
          + Create Batch
        </Link>
      </div>

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <label htmlFor="status-filter" className="text-sm text-ink-2">
          Status
        </label>
        <select
          id="status-filter"
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value as BatchStatus | "all")}
          className="rounded-md border border-border bg-background px-3 py-1.5 text-sm"
        >
          <option value="all">All</option>
          <option value="upcoming">Upcoming</option>
          <option value="ongoing">Ongoing</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>

        <label htmlFor="course-filter" className="text-sm text-ink-2">
          Course
        </label>
        <select
          id="course-filter"
          value={courseFilter}
          onChange={(e) =>
            setCourseFilter(e.target.value === "all" ? "all" : Number(e.target.value))
          }
          className="rounded-md border border-border bg-background px-3 py-1.5 text-sm"
        >
          <option value="all">All courses</option>
          {courses.map((c) => (
            <option key={c.id} value={c.id}>
              {c.title}
            </option>
          ))}
        </select>

        {batches !== "loading" && <span className="text-sm text-ink-3">Total: {batches.length}</span>}
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <div className="overflow-x-auto rounded-xl border border-border bg-background">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-border bg-surface text-xs uppercase tracking-wide text-ink-3">
            <tr>
              <th className="px-4 py-3">Batch</th>
              <th className="px-4 py-3">Course</th>
              <th className="px-4 py-3">Duration</th>
              <th className="px-4 py-3">Instructors</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            {batches === "loading" && (
              <tr>
                <td className="px-4 py-6 text-ink-3" colSpan={6}>
                  Loading…
                </td>
              </tr>
            )}
            {batches !== "loading" && batches.length === 0 && (
              <tr>
                <td className="px-4 py-6 text-ink-3" colSpan={6}>
                  No batches yet.
                </td>
              </tr>
            )}
            {batches !== "loading" &&
              batches.map((batch) => (
                <tr key={batch.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3 font-medium text-ink">{batch.name}</td>
                  <td className="px-4 py-3 text-ink-2">{batch.course_title ?? "—"}</td>
                  <td className="px-4 py-3 text-ink-2">
                    {batch.start_date} – {batch.end_date}
                  </td>
                  <td className="px-4 py-3 text-ink-2">
                    {batch.instructors.length
                      ? batch.instructors.map((i) => i.name).join(", ")
                      : "Unassigned"}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLE[batch.status]}`}
                    >
                      {batch.status}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <Link
                      href={`/admin/batches/${batch.id}`}
                      className="text-sm font-medium text-brand-primary hover:underline"
                    >
                      Manage
                    </Link>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
