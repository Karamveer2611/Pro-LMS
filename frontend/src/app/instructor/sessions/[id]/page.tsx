"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import {
  getSessionAttendance,
  getSessionRoster,
  saveAttendance,
  type AttendanceStatus,
} from "@/lib/attendance";
import { getSession, type LiveSession } from "@/lib/sessions";

type RosterRow = {
  id: number;
  name: string;
  email: string;
  status: AttendanceStatus | null;
};

const STATUS_OPTIONS: { value: AttendanceStatus; label: string }[] = [
  { value: "present", label: "Present" },
  { value: "absent", label: "Absent" },
  { value: "excused", label: "Excused" },
];

export default function SessionAttendancePage() {
  const { id } = useParams<{ id: string }>();
  const sessionId = Number(id);

  const [session, setSession] = useState<LiveSession | "loading">("loading");
  const [rows, setRows] = useState<RosterRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    getSession(sessionId).then(setSession).catch(() => setError("Couldn't load this session."));

    Promise.all([getSessionRoster(sessionId), getSessionAttendance(sessionId)])
      .then(([roster, attendance]) => {
        const byUser = new Map(attendance.map((a) => [a.user.id, a.status]));
        setRows(
          roster.map((learner) => ({
            ...learner,
            status: byUser.get(learner.id) ?? null,
          })),
        );
      })
      .catch(() => setError("Couldn't load the roster."));
  }, [sessionId]);

  function setStatus(userId: number, status: AttendanceStatus) {
    setSaved(false);
    setRows((rs) => rs.map((r) => (r.id === userId ? { ...r, status } : r)));
  }

  function markAllPresent() {
    setSaved(false);
    setRows((rs) => rs.map((r) => ({ ...r, status: "present" })));
  }

  async function handleSave() {
    setSaving(true);
    setError(null);
    try {
      const records = rows
        .filter((r): r is RosterRow & { status: AttendanceStatus } => r.status !== null)
        .map((r) => ({ user_id: r.id, status: r.status }));
      await saveAttendance(sessionId, records);
      setSaved(true);
    } catch {
      setError("Couldn't save attendance. Please try again.");
    } finally {
      setSaving(false);
    }
  }

  if (error) return <p className="text-sm text-red-600">{error}</p>;
  if (session === "loading") return <p className="text-sm text-ink-3">Loading…</p>;

  return (
    <div className="max-w-2xl">
      <h1 className="mb-1 font-heading text-xl font-semibold text-ink">{session.title}</h1>
      <p className="mb-6 text-sm text-ink-3">
        {session.batch_name} · {new Date(session.scheduled_at).toLocaleString()}
      </p>

      <div className="rounded-xl border border-border bg-background p-6">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="font-heading text-sm font-semibold text-ink">Learners</h2>
          <button
            onClick={markAllPresent}
            className="text-sm font-medium text-brand-primary hover:underline"
          >
            Mark All Present
          </button>
        </div>

        {rows.length === 0 && (
          <p className="text-sm text-ink-3">No learners enrolled in this batch yet.</p>
        )}

        <ul className="space-y-2">
          {rows.map((row) => (
            <li
              key={row.id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div>
                <p className="text-sm font-medium text-ink">{row.name}</p>
                <p className="text-xs text-ink-3">{row.email}</p>
              </div>
              <div className="flex gap-1">
                {STATUS_OPTIONS.map((opt) => (
                  <button
                    key={opt.value}
                    onClick={() => setStatus(row.id, opt.value)}
                    className={`rounded-full px-3 py-1 text-xs font-medium ${
                      row.status === opt.value
                        ? opt.value === "present"
                          ? "bg-brand-accent text-white"
                          : opt.value === "absent"
                            ? "bg-red-600 text-white"
                            : "bg-brand-primary text-white"
                        : "border border-border text-ink-2 hover:border-brand-primary"
                    }`}
                  >
                    {opt.label}
                  </button>
                ))}
              </div>
            </li>
          ))}
        </ul>

        <div className="mt-5 flex items-center gap-3">
          <button
            onClick={handleSave}
            disabled={saving || rows.length === 0}
            className="rounded-md bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark disabled:opacity-60"
          >
            {saving ? "Saving…" : "Save Attendance"}
          </button>
          {saved && <span className="text-sm text-brand-accent-dark">Attendance saved successfully.</span>}
        </div>
      </div>
    </div>
  );
}
