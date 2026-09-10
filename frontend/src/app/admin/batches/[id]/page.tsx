"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import {
  assignInstructor,
  getBatch,
  listInstructors,
  unassignInstructor,
  type Batch,
  type Instructor,
} from "@/lib/batches";

export default function AdminBatchDetailPage() {
  const { id } = useParams<{ id: string }>();
  const batchId = Number(id);

  const [batch, setBatch] = useState<Batch | "loading">("loading");
  const [instructors, setInstructors] = useState<Instructor[]>([]);
  const [selectedInstructor, setSelectedInstructor] = useState("");
  const [error, setError] = useState<string | null>(null);

  function reload() {
    getBatch(batchId)
      .then(setBatch)
      .catch(() => setError("Couldn't load this batch."));
  }

  useEffect(reload, [batchId]);
  useEffect(() => {
    listInstructors().then(setInstructors).catch(() => {});
  }, []);

  async function handleAssign() {
    if (!selectedInstructor) return;
    setError(null);
    try {
      await assignInstructor(batchId, Number(selectedInstructor));
      setSelectedInstructor("");
      reload();
    } catch {
      setError("Couldn't assign that instructor.");
    }
  }

  async function handleUnassign(userId: number) {
    await unassignInstructor(batchId, userId);
    reload();
  }

  if (batch === "loading") {
    return <p className="text-sm text-ink-3">Loading…</p>;
  }

  const assignedIds = new Set(batch.instructors.map((i) => i.id));
  const availableInstructors = instructors.filter((i) => !assignedIds.has(i.id));

  return (
    <div className="max-w-2xl">
      <Link href="/admin/batches" className="text-sm text-brand-primary hover:underline">
        ← Back to Batches
      </Link>

      <h1 className="mt-2 mb-1 font-heading text-xl font-semibold text-ink">{batch.name}</h1>
      <p className="mb-5 text-sm text-ink-3">
        {batch.course_title} · {batch.start_date} – {batch.end_date} ·{" "}
        <span className="capitalize">{batch.status}</span>
      </p>

      {error && <p className="mb-3 text-sm text-red-600">{error}</p>}

      <div className="rounded-xl border border-border bg-background p-6">
        <h2 className="mb-3 font-heading text-sm font-semibold text-ink">Instructors</h2>

        {batch.instructors.length === 0 && (
          <p className="mb-4 text-sm text-ink-3">No instructors assigned yet.</p>
        )}

        <ul className="mb-4 space-y-2">
          {batch.instructors.map((instructor) => (
            <li
              key={instructor.id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
            >
              <span className="text-ink">
                {instructor.name}{" "}
                <span className="text-ink-3">({instructor.email})</span>
              </span>
              <button
                onClick={() => handleUnassign(instructor.id)}
                className="text-xs font-medium text-red-600 hover:underline"
              >
                Remove
              </button>
            </li>
          ))}
        </ul>

        <div className="flex items-center gap-2">
          <select
            value={selectedInstructor}
            onChange={(e) => setSelectedInstructor(e.target.value)}
            className="flex-1 rounded-md border border-border px-3 py-2 text-sm outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
          >
            <option value="">Select an instructor to assign…</option>
            {availableInstructors.map((instructor) => (
              <option key={instructor.id} value={instructor.id}>
                {instructor.name} ({instructor.email})
              </option>
            ))}
          </select>
          <button
            onClick={handleAssign}
            disabled={!selectedInstructor}
            className="rounded-md bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-dark disabled:opacity-60"
          >
            Assign
          </button>
        </div>
      </div>
    </div>
  );
}
