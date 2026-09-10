"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { listCourses, setCourseStatus, type Course, type CourseStatus } from "@/lib/courses";

const STATUS_STYLE: Record<CourseStatus, string> = {
  draft: "bg-surface text-ink-3 border border-border",
  published: "bg-brand-accent/10 text-brand-accent-dark",
  archived: "bg-red-50 text-red-700",
};

const FORMAT_LABEL: Record<Course["format"], string> = {
  self_paced: "Self-paced",
  instructor_led: "Instructor-led",
  hybrid: "Hybrid",
};

export default function AdminCoursesPage() {
  const [courses, setCourses] = useState<Course[] | "loading">("loading");
  const [statusFilter, setStatusFilter] = useState<CourseStatus | "all">("all");
  const [error, setError] = useState<string | null>(null);

  function reload() {
    listCourses()
      .then(setCourses)
      .catch(() => setError("Couldn't load courses."));
  }

  useEffect(reload, []);

  async function togglePublish(course: Course) {
    const next = course.status === "published" ? "draft" : "published";
    await setCourseStatus(course.id, next);
    reload();
  }

  const visible =
    courses === "loading"
      ? []
      : courses.filter((c) => statusFilter === "all" || c.status === statusFilter);

  return (
    <div>
      <div className="mb-5 flex items-center justify-between">
        <h1 className="font-heading text-xl font-semibold text-ink">Courses</h1>
        <Link
          href="/admin/courses/new"
          className="rounded-md bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark"
        >
          + Create Course
        </Link>
      </div>

      <div className="mb-4 flex items-center gap-3">
        <label htmlFor="status-filter" className="text-sm text-ink-2">
          Status
        </label>
        <select
          id="status-filter"
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value as CourseStatus | "all")}
          className="rounded-md border border-border bg-background px-3 py-1.5 text-sm"
        >
          <option value="all">All</option>
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
        {courses !== "loading" && (
          <span className="text-sm text-ink-3">Total: {visible.length}</span>
        )}
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <div className="overflow-x-auto rounded-xl border border-border bg-background">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-border bg-surface text-xs uppercase tracking-wide text-ink-3">
            <tr>
              <th className="px-4 py-3">Title</th>
              <th className="px-4 py-3">Format</th>
              <th className="px-4 py-3">Price</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            {courses === "loading" && (
              <tr>
                <td className="px-4 py-6 text-ink-3" colSpan={5}>
                  Loading…
                </td>
              </tr>
            )}
            {courses !== "loading" && visible.length === 0 && (
              <tr>
                <td className="px-4 py-6 text-ink-3" colSpan={5}>
                  No courses yet.
                </td>
              </tr>
            )}
            {visible.map((course) => (
              <tr key={course.id} className="border-b border-border last:border-0">
                <td className="px-4 py-3 font-medium text-ink">{course.title}</td>
                <td className="px-4 py-3 text-ink-2">{FORMAT_LABEL[course.format]}</td>
                <td className="px-4 py-3 text-ink-2">
                  {course.price ? `${course.currency ?? "INR"} ${course.price}` : "—"}
                </td>
                <td className="px-4 py-3">
                  <span
                    className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLE[course.status]}`}
                  >
                    {course.status}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <button
                    onClick={() => togglePublish(course)}
                    className="text-sm font-medium text-brand-primary hover:underline"
                  >
                    {course.status === "published" ? "Unpublish" : "Publish"}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
