import { apiFetch } from "./api";
import { getToken } from "./auth";

export type CourseFormat = "self_paced" | "instructor_led" | "hybrid";
export type CourseStatus = "draft" | "published" | "archived";

export type Course = {
  id: number;
  title: string;
  slug: string;
  short_description: string | null;
  description: string | null;
  format: CourseFormat;
  price: string | null;
  discount_price: string | null;
  currency: string | null;
  default_access_days: number | null;
  status: CourseStatus;
  created_at: string;
};

export type Category = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
};

export async function listCourses(): Promise<Course[]> {
  const { data } = await apiFetch<{ data: Course[] }>("/api/v1/courses", {
    token: getToken(),
  });
  return data;
}

export async function listCategories(): Promise<Category[]> {
  const { data } = await apiFetch<{ data: Category[] }>("/api/v1/categories", {
    token: getToken(),
  });
  return data;
}

export async function createCourse(payload: {
  title: string;
  short_description?: string;
  description?: string;
  format: CourseFormat;
  price?: number;
  discount_price?: number;
  default_access_days?: number;
  category_ids?: number[];
}): Promise<Course> {
  const { data } = await apiFetch<{ data: Course }>("/api/v1/courses", {
    method: "POST",
    token: getToken(),
    body: JSON.stringify(payload),
  });
  return data;
}

export async function setCourseStatus(id: number, status: CourseStatus): Promise<Course> {
  const { data } = await apiFetch<{ data: Course }>(`/api/v1/courses/${id}/status`, {
    method: "PATCH",
    token: getToken(),
    body: JSON.stringify({ status }),
  });
  return data;
}
