import { apiFetch } from "./api";
import { getToken } from "./auth";

export type AttendanceStatus = "present" | "absent" | "excused";

export type AttendanceRecord = {
  id: number;
  session_id: number;
  user: { id: number; name: string; email: string };
  status: AttendanceStatus;
  notes: string | null;
  marked_by: number;
};

export async function getSessionRoster(
  sessionId: number,
): Promise<{ id: number; name: string; email: string }[]> {
  const { data } = await apiFetch<{ data: { id: number; name: string; email: string }[] }>(
    `/api/v1/sessions/${sessionId}/roster`,
    { token: getToken() },
  );
  return data;
}

export async function getSessionAttendance(sessionId: number): Promise<AttendanceRecord[]> {
  const { data } = await apiFetch<{ data: AttendanceRecord[] }>(
    `/api/v1/sessions/${sessionId}/attendance`,
    { token: getToken() },
  );
  return data;
}

export async function saveAttendance(
  sessionId: number,
  records: { user_id: number; status: AttendanceStatus; notes?: string }[],
): Promise<AttendanceRecord[]> {
  const { data } = await apiFetch<{ data: AttendanceRecord[] }>(
    `/api/v1/sessions/${sessionId}/attendance`,
    { method: "POST", token: getToken(), body: JSON.stringify({ records }) },
  );
  return data;
}
