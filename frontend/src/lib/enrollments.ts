import { apiFetch } from "./api";
import { getToken } from "./auth";
import type { Course } from "./courses";

export type EnrollmentStatus = "active" | "expired" | "cancelled" | "completed";

export type Enrollment = {
  id: number;
  user: { id: number; name: string; email: string };
  course: Course;
  batch_id: number | null;
  source: "manual";
  enrolled_at: string;
  expires_at: string | null;
  status: EnrollmentStatus;
  enrolled_by: number | null;
  notes: string | null;
};

export async function listLearners(): Promise<{ id: number; name: string; email: string }[]> {
  const { data } = await apiFetch<{ data: { id: number; name: string; email: string }[] }>(
    "/api/v1/users?role=learner",
    { token: getToken() },
  );
  return data;
}

export async function listMyEnrollments(): Promise<Enrollment[]> {
  const { data } = await apiFetch<{ data: Enrollment[] }>("/api/v1/enrollments", {
    token: getToken(),
  });
  return data;
}

export async function listAllEnrollments(filters?: {
  course_id?: number;
  status?: EnrollmentStatus;
}): Promise<Enrollment[]> {
  const params = new URLSearchParams();
  if (filters?.course_id) params.set("course_id", String(filters.course_id));
  if (filters?.status) params.set("status", filters.status);

  const { data } = await apiFetch<{ data: Enrollment[] }>(`/api/v1/enrollments?${params}`, {
    token: getToken(),
  });
  return data;
}

export async function createEnrollment(payload: {
  user_id: number;
  course_id: number;
  batch_id?: number;
  notes?: string;
}): Promise<Enrollment> {
  const { data } = await apiFetch<{ data: Enrollment }>("/api/v1/enrollments", {
    method: "POST",
    token: getToken(),
    body: JSON.stringify(payload),
  });
  return data;
}

export async function extendEnrollment(id: number, expiresAt: string): Promise<Enrollment> {
  const { data } = await apiFetch<{ data: Enrollment }>(`/api/v1/enrollments/${id}/expiry`, {
    method: "PATCH",
    token: getToken(),
    body: JSON.stringify({ expires_at: expiresAt }),
  });
  return data;
}

export async function cancelEnrollment(id: number): Promise<Enrollment> {
  const { data } = await apiFetch<{ data: Enrollment }>(`/api/v1/enrollments/${id}/cancel`, {
    method: "POST",
    token: getToken(),
  });
  return data;
}
