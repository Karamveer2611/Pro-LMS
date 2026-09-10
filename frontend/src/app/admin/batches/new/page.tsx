"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import { createBatch, listAllCoursesForFilter } from "@/lib/batches";
import type { Course } from "@/lib/courses";

export default function NewBatchPage() {
  const router = useRouter();
  const [courses, setCourses] = useState<Course[]>([]);
  const [form, setForm] = useState({
    course_id: "",
    name: "",
    start_date: "",
    end_date: "",
    capacity: "",
  });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    listAllCoursesForFilter().then(setCourses).catch(() => {});
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setErrors({});

    if (!form.course_id) {
      setErrors({ course_id: ["Select a course."] });
      return;
    }

    setSubmitting(true);
    try {
      await createBatch(Number(form.course_id), {
        name: form.name,
        start_date: form.start_date,
        end_date: form.end_date,
        capacity: form.capacity ? Number(form.capacity) : undefined,
      });
      router.push("/admin/batches");
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setErrors(err.errors);
      } else {
        setErrors({ name: ["Something went wrong. Please try again."] });
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="max-w-xl">
      <h1 className="mb-5 font-heading text-xl font-semibold text-ink">Create Batch</h1>

      <form
        onSubmit={handleSubmit}
        className="space-y-5 rounded-xl border border-border bg-background p-6"
      >
        <div>
          <label htmlFor="course_id" className="block text-sm font-medium text-ink-2">
            Course
          </label>
          <select
            id="course_id"
            value={form.course_id}
            onChange={(e) => setForm((f) => ({ ...f, course_id: e.target.value }))}
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
        </div>

        <div>
          <label htmlFor="name" className="block text-sm font-medium text-ink-2">
            Batch name
          </label>
          <input
            id="name"
            required
            value={form.name}
            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          />
          {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name[0]}</p>}
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label htmlFor="start_date" className="block text-sm font-medium text-ink-2">
              Start date
            </label>
            <input
              id="start_date"
              type="date"
              required
              value={form.start_date}
              onChange={(e) => setForm((f) => ({ ...f, start_date: e.target.value }))}
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            />
          </div>
          <div>
            <label htmlFor="end_date" className="block text-sm font-medium text-ink-2">
              End date
            </label>
            <input
              id="end_date"
              type="date"
              required
              value={form.end_date}
              onChange={(e) => setForm((f) => ({ ...f, end_date: e.target.value }))}
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            />
            {errors.end_date && <p className="mt-1 text-xs text-red-600">{errors.end_date[0]}</p>}
          </div>
        </div>

        <div>
          <label htmlFor="capacity" className="block text-sm font-medium text-ink-2">
            Capacity (optional)
          </label>
          <input
            id="capacity"
            type="number"
            min={1}
            value={form.capacity}
            onChange={(e) => setForm((f) => ({ ...f, capacity: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          />
        </div>

        <div className="flex items-center gap-3 pt-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-md bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark disabled:opacity-60"
          >
            {submitting ? "Creating…" : "Create Batch"}
          </button>
          <button
            type="button"
            onClick={() => router.push("/admin/batches")}
            className="text-sm font-medium text-ink-2 hover:text-ink"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  );
}
