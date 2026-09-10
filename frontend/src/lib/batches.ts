import { apiFetch } from "./api";
import { getToken } from "./auth";
import type { Course } from "./courses";

export type BatchStatus = "upcoming" | "ongoing" | "completed" | "cancelled";

export type Instructor = {
  id: number;
  name: string;
  email: string;
};

export type Batch = {
  id: number;
  name: string;
  course_id: number;
  course_title: string | null;
  start_date: string;
  end_date: string;
  status: BatchStatus;
  access_days_override: number | null;
  capacity: number | null;
  instructors: Instructor[];
};

export async function listBatches(filters?: {
  status?: BatchStatus;
  course_id?: number;
}): Promise<Batch[]> {
  const params = new URLSearchParams();
  if (filters?.status) params.set("status", filters.status);
  if (filters?.course_id) params.set("course_id", String(filters.course_id));

  const { data } = await apiFetch<{ data: Batch[] }>(`/api/v1/batches?${params}`, {
    token: getToken(),
  });
  return data;
}

export async function getBatch(id: number): Promise<Batch> {
  const { data } = await apiFetch<{ data: Batch }>(`/api/v1/batches/${id}`, {
    token: getToken(),
  });
  return data;
}

export async function createBatch(
  courseId: number,
  payload: {
    name: string;
    start_date: string;
    end_date: string;
    capacity?: number;
    access_days_override?: number;
  },
): Promise<Batch> {
  const { data } = await apiFetch<{ data: Batch }>(`/api/v1/courses/${courseId}/batches`, {
    method: "POST",
    token: getToken(),
    body: JSON.stringify(payload),
  });
  return data;
}

export async function listInstructors(): Promise<Instructor[]> {
  const { data } = await apiFetch<{ data: Instructor[] }>("/api/v1/users?role=instructor", {
    token: getToken(),
  });
  return data;
}

export async function assignInstructor(batchId: number, userId: number): Promise<Batch> {
  const { data } = await apiFetch<{ data: Batch }>(`/api/v1/batches/${batchId}/instructors`, {
    method: "POST",
    token: getToken(),
    body: JSON.stringify({ user_id: userId }),
  });
  return data;
}

export async function unassignInstructor(batchId: number, userId: number): Promise<Batch> {
  const { data } = await apiFetch<{ data: Batch }>(
    `/api/v1/batches/${batchId}/instructors/${userId}`,
    { method: "DELETE", token: getToken() },
  );
  return data;
}

export async function listAllCoursesForFilter(): Promise<Course[]> {
  const { data } = await apiFetch<{ data: Course[] }>("/api/v1/courses", { token: getToken() });
  return data;
}
