"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import {
  createCourse,
  listCategories,
  type Category,
  type CourseFormat,
} from "@/lib/courses";

export default function NewCoursePage() {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [form, setForm] = useState({
    title: "",
    short_description: "",
    description: "",
    format: "self_paced" as CourseFormat,
    price: "",
    discount_price: "",
    default_access_days: "90",
  });
  const [categoryIds, setCategoryIds] = useState<number[]>([]);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    listCategories().then(setCategories).catch(() => {});
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setErrors({});
    setSubmitting(true);

    try {
      await createCourse({
        title: form.title,
        short_description: form.short_description || undefined,
        description: form.description || undefined,
        format: form.format,
        price: form.price ? Number(form.price) : undefined,
        discount_price: form.discount_price ? Number(form.discount_price) : undefined,
        default_access_days: form.default_access_days
          ? Number(form.default_access_days)
          : undefined,
        category_ids: categoryIds.length ? categoryIds : undefined,
      });
      router.push("/admin/courses");
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setErrors(err.errors);
      } else {
        setErrors({ title: ["Something went wrong. Please try again."] });
      }
    } finally {
      setSubmitting(false);
    }
  }

  function toggleCategory(id: number) {
    setCategoryIds((ids) => (ids.includes(id) ? ids.filter((c) => c !== id) : [...ids, id]));
  }

  return (
    <div className="max-w-2xl">
      <h1 className="mb-5 font-heading text-xl font-semibold text-ink">Create Course</h1>

      <form
        onSubmit={handleSubmit}
        className="space-y-5 rounded-xl border border-border bg-background p-6"
      >
        <div>
          <label htmlFor="title" className="block text-sm font-medium text-ink-2">
            Title
          </label>
          <input
            id="title"
            required
            value={form.title}
            onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          />
          {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title[0]}</p>}
        </div>

        <div>
          <label htmlFor="short_description" className="block text-sm font-medium text-ink-2">
            Short description
          </label>
          <input
            id="short_description"
            value={form.short_description}
            onChange={(e) => setForm((f) => ({ ...f, short_description: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          />
        </div>

        <div>
          <label htmlFor="description" className="block text-sm font-medium text-ink-2">
            Description
          </label>
          <textarea
            id="description"
            rows={4}
            value={form.description}
            onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          />
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label htmlFor="format" className="block text-sm font-medium text-ink-2">
              Format
            </label>
            <select
              id="format"
              value={form.format}
              onChange={(e) =>
                setForm((f) => ({ ...f, format: e.target.value as CourseFormat }))
              }
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            >
              <option value="self_paced">Self-paced</option>
              <option value="instructor_led">Instructor-led</option>
              <option value="hybrid">Hybrid</option>
            </select>
          </div>
          <div>
            <label htmlFor="default_access_days" className="block text-sm font-medium text-ink-2">
              Access duration (days)
            </label>
            <input
              id="default_access_days"
              type="number"
              min={1}
              value={form.default_access_days}
              onChange={(e) => setForm((f) => ({ ...f, default_access_days: e.target.value }))}
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label htmlFor="price" className="block text-sm font-medium text-ink-2">
              Price (₹)
            </label>
            <input
              id="price"
              type="number"
              min={0}
              value={form.price}
              onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))}
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            />
          </div>
          <div>
            <label htmlFor="discount_price" className="block text-sm font-medium text-ink-2">
              Discount price (₹)
            </label>
            <input
              id="discount_price"
              type="number"
              min={0}
              value={form.discount_price}
              onChange={(e) => setForm((f) => ({ ...f, discount_price: e.target.value }))}
              className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            />
            {errors.discount_price && (
              <p className="mt-1 text-xs text-red-600">{errors.discount_price[0]}</p>
            )}
          </div>
        </div>

        {categories.length > 0 && (
          <div>
            <span className="block text-sm font-medium text-ink-2">Categories</span>
            <div className="mt-1 flex flex-wrap gap-2">
              {categories.map((category) => (
                <button
                  type="button"
                  key={category.id}
                  onClick={() => toggleCategory(category.id)}
                  className={`rounded-full border px-3 py-1 text-xs font-medium ${
                    categoryIds.includes(category.id)
                      ? "border-brand-primary bg-brand-primary-soft text-brand-primary"
                      : "border-border text-ink-2"
                  }`}
                >
                  {category.name}
                </button>
              ))}
            </div>
          </div>
        )}

        <div className="flex items-center gap-3 pt-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-md bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark disabled:opacity-60"
          >
            {submitting ? "Creating…" : "Create Course"}
          </button>
          <button
            type="button"
            onClick={() => router.push("/admin/courses")}
            className="text-sm font-medium text-ink-2 hover:text-ink"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  );
}
