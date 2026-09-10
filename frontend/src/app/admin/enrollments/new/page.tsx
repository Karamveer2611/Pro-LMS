"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import { listBatches, type Batch } from "@/lib/batches";
import { listAllCoursesForFilter } from "@/lib/batches";
import type { Course } from "@/lib/courses";
import { createEnrollment, listLearners } from "@/lib/enrollments";

export default function NewEnrollmentPage() {
  const router = useRouter();
  const [learners, setLearners] = useState<{ id: number; name: string; email: string }[]>([]);
  const [courses, setCourses] = useState<Course[]>([]);
  const [batches, setBatches] = useState<Batch[]>([]);
  const [form, setForm] = useState({ user_id: "", course_id: "", batch_id: "", notes: "" });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    listLearners().then(setLearners).catch(() => {});
    listAllCoursesForFilter().then(setCourses).catch(() => {});
  }, []);

  useEffect(() => {
    if (!form.course_id) return;
    listBatches({ course_id: Number(form.course_id) })
      .then(setBatches)
      .catch(() => setBatches([]));
  }, [form.course_id]);

  const selectedCourse = courses.find((c) => c.id === Number(form.course_id));
  const availableBatches = form.course_id ? batches : [];

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setErrors({});

    if (!form.user_id || !form.course_id) {
      setErrors({ course_id: ["Select a learner and a course."] });
      return;
    }

    setSubmitting(true);
    try {
      await createEnrollment({
        user_id: Number(form.user_id),
        course_id: Number(form.course_id),
        batch_id: form.batch_id ? Number(form.batch_id) : undefined,
        notes: form.notes || undefined,
      });
      router.push("/admin/enrollments");
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setErrors(err.errors);
      } else {
        setErrors({ course_id: ["Something went wrong. Please try again."] });
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="max-w-xl">
      <h1 className="mb-5 font-heading text-xl font-semibold text-ink">Enroll a Learner</h1>

      <form
        onSubmit={handleSubmit}
        className="space-y-5 rounded-xl border border-border bg-background p-6"
      >
        <div>
          <label htmlFor="user_id" className="block text-sm font-medium text-ink-2">
            Learner
          </label>
          <select
            id="user_id"
            value={form.user_id}
            onChange={(e) => setForm((f) => ({ ...f, user_id: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          >
            <option value="">Select a learner…</option>
            {learners.map((l) => (
              <option key={l.id} value={l.id}>
                {l.name} ({l.email})
              </option>
            ))}
          </select>
          {errors.user_id && <p className="mt-1 text-xs text-red-600">{errors.user_id[0]}</p>}
        </div>

        <div>
          <label htmlFor="course_id" className="block text-sm font-medium text-ink-2">
            Course
          </label>
          <select
            id="course_id"
            value={form.course_id}
            onChange={(e) => setForm((f) => ({ ...f, course_id: e.target.value, batch_id: "" }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          >
            <option value="">Select a course…</option>
            {courses.map((c) => (
              <option key={c.id} value={c.id}>
                {c.title}
              </option>
            ))}
          </select>
          {errors.course_id && <p className="mt-1 text-xs text-red-600">{errors.course_id[0]}</p>}
          {selectedCourse && (
            <p className="mt-1 text-xs text-ink-3">
              Default access:{" "}
              {selectedCourse.default_access_days
                ? `${selectedCourse.default_access_days} days`
                : "Lifetime"}
            </p>
          )}
        </div>

        {availableBatches.length > 0 && (
          <div>
            <label htmlFor="batch_id" className="block text-sm font-medium text-ink-2">
              Batch (optional)
            </label>
            <select
              id="batch_id"
              value={form.batch_id}
              onChange={(e) => setForm((f) => ({ ...f, batch_id: e.target.value }))}
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            >
              <option value="">No batch (direct enrollment)</option>
              {availableBatches.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name} ({b.start_date} – {b.end_date})
                  {b.access_days_override ? ` — ${b.access_days_override} day access` : ""}
                </option>
              ))}
            </select>
            {errors.batch_id && <p className="mt-1 text-xs text-red-600">{errors.batch_id[0]}</p>}
          </div>
        )}

        <div>
          <label htmlFor="notes" className="block text-sm font-medium text-ink-2">
            Notes (optional)
          </label>
          <textarea
            id="notes"
            rows={2}
            value={form.notes}
            onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          />
        </div>

        <div className="flex items-center gap-3 pt-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-md bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark disabled:opacity-60"
          >
            {submitting ? "Enrolling…" : "Enroll Learner"}
          </button>
          <button
            type="button"
            onClick={() => router.push("/admin/enrollments")}
            className="text-sm font-medium text-ink-2 hover:text-ink"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  );
}
