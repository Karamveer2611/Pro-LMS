"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { listMySessions, type LiveSession } from "@/lib/sessions";

const STATUS_STYLE: Record<LiveSession["status"], string> = {
  scheduled: "bg-brand-primary-soft text-brand-primary",
  completed: "bg-brand-accent/10 text-brand-accent-dark",
  cancelled: "bg-red-50 text-red-700",
};

export default function InstructorSessionsPage() {
  const [sessions, setSessions] = useState<LiveSession[] | "loading">("loading");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listMySessions()
      .then(setSessions)
      .catch(() => setError("Couldn't load your sessions."));
  }, []);

  return (
    <div>
      <h1 className="mb-1 font-heading text-2xl font-semibold text-ink">My Sessions</h1>
      <p className="mb-6 text-sm text-ink-3">Live sessions you&apos;re assigned to teach.</p>

      {error && <p className="text-sm text-red-600">{error}</p>}
      {sessions === "loading" && <p className="text-sm text-ink-3">Loading…</p>}

      {sessions !== "loading" && sessions.length === 0 && (
        <div className="rounded-xl border border-border bg-background p-8 text-center">
          <p className="text-sm text-ink-3">No sessions scheduled for you yet.</p>
        </div>
      )}

      <div className="space-y-3">
        {sessions !== "loading" &&
          sessions.map((session) => (
            <Link
              key={session.id}
              href={`/instructor/sessions/${session.id}`}
              className="flex items-center justify-between rounded-xl border border-border bg-background p-5 shadow-sm hover:shadow-md"
            >
              <div>
                <h2 className="font-heading text-base font-semibold text-ink">{session.title}</h2>
                <p className="text-sm text-ink-3">
                  {session.batch_name} · {new Date(session.scheduled_at).toLocaleString()}
                </p>
              </div>
              <span
                className={`rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${STATUS_STYLE[session.status]}`}
              >
                {session.status}
              </span>
            </Link>
          ))}
      </div>
    </div>
  );
}
