import { apiFetch } from "./api";
import { getToken } from "./auth";

export type SessionStatus = "scheduled" | "completed" | "cancelled";

export type LiveSession = {
  id: number;
  batch_id: number;
  batch_name: string | null;
  instructor: { id: number; name: string; email: string };
  title: string;
  description: string | null;
  scheduled_at: string;
  duration_minutes: number;
  meeting_provider: string | null;
  meeting_link: string | null;
  status: SessionStatus;
  recording_url: string | null;
};

export async function listMySessions(): Promise<LiveSession[]> {
  const { data } = await apiFetch<{ data: LiveSession[] }>("/api/v1/sessions", {
    token: getToken(),
  });
  return data;
}

export async function getSession(id: number): Promise<LiveSession> {
  const { data } = await apiFetch<{ data: LiveSession }>(`/api/v1/sessions/${id}`, {
    token: getToken(),
  });
  return data;
}

export async function createSession(
  batchId: number,
  payload: { instructor_id: number; title: string; scheduled_at: string; meeting_link?: string },
): Promise<LiveSession> {
  const { data } = await apiFetch<{ data: LiveSession }>(`/api/v1/batches/${batchId}/sessions`, {
    method: "POST",
    token: getToken(),
    body: JSON.stringify(payload),
  });
  return data;
}
