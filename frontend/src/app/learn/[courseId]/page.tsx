"use client";

import { useParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import {
  getCourseCurriculum,
  getLessonContent,
  type CourseWithCurriculum,
  type CurriculumLesson,
  type LessonContent,
} from "@/lib/learn";

function LockIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="currentColor" className="h-3.5 w-3.5">
      <path
        fillRule="evenodd"
        d="M10 1a4 4 0 00-4 4v2H5a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V5a4 4 0 00-4-4zm2 6V5a2 2 0 10-4 0v2h4z"
        clipRule="evenodd"
      />
    </svg>
  );
}

export default function CoursePlayerPage() {
  const { courseId } = useParams<{ courseId: string }>();
  const [course, setCourse] = useState<CourseWithCurriculum | "loading">("loading");
  const [error, setError] = useState<string | null>(null);
  const [activeLessonId, setActiveLessonId] = useState<number | null>(null);
  const [content, setContent] = useState<LessonContent | null>(null);

  useEffect(() => {
    getCourseCurriculum(Number(courseId))
      .then((c) => {
        setCourse(c);
        const firstUnlocked = c.modules
          .flatMap((m) => m.sections)
          .flatMap((s) => s.lessons)
          .find((l) => l.is_unlocked);
        if (firstUnlocked) setActiveLessonId(firstUnlocked.id);
      })
      .catch(() => setError("Couldn't load this course. You may not be enrolled."));
  }, [courseId]);

  const activeLesson = useMemo<CurriculumLesson | undefined>(() => {
    if (course === "loading" || !activeLessonId) return undefined;
    return course.modules
      .flatMap((m) => m.sections)
      .flatMap((s) => s.lessons)
      .find((l) => l.id === activeLessonId);
  }, [course, activeLessonId]);

  useEffect(() => {
    if (!activeLesson?.is_unlocked) return;
    getLessonContent(activeLesson.id)
      .then(setContent)
      .catch(() => setContent(null));
  }, [activeLesson]);

  // "loading" is derived, not stored: true whenever the unlocked lesson
  // we want content for doesn't match what's currently in `content` yet.
  const isLoadingContent = activeLesson?.is_unlocked && content?.id !== activeLesson.id;

  if (error) return <p className="text-sm text-red-600">{error}</p>;
  if (course === "loading") return <p className="text-sm text-ink-3">Loading…</p>;

  return (
    <div>
      <h1 className="mb-1 font-heading text-xl font-semibold text-ink">{course.title}</h1>
      {course.short_description && <p className="mb-6 text-sm text-ink-3">{course.short_description}</p>}

      <div className="grid gap-6 md:grid-cols-[280px_1fr]">
        <nav className="space-y-4 rounded-xl border border-border bg-background p-4">
          {course.modules.map((module) => (
            <div key={module.id}>
              <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-3">
                {module.title}
              </h2>
              {module.sections.map((section) => (
                <div key={section.id} className="mb-3">
                  <p className="mb-1 text-xs font-medium text-ink-2">{section.title}</p>
                  <ul className="space-y-0.5">
                    {section.lessons.map((lesson) => (
                      <li key={lesson.id}>
                        <button
                          onClick={() => setActiveLessonId(lesson.id)}
                          disabled={!lesson.is_unlocked}
                          className={`flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm ${
                            lesson.id === activeLessonId
                              ? "bg-brand-primary-soft text-brand-primary"
                              : lesson.is_unlocked
                                ? "text-ink-2 hover:bg-surface"
                                : "cursor-not-allowed text-ink-3"
                          }`}
                        >
                          {!lesson.is_unlocked && <LockIcon />}
                          <span className="truncate">{lesson.title}</span>
                        </button>
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          ))}
        </nav>

        <div className="rounded-xl border border-border bg-background p-6">
          {!activeLesson && (
            <p className="text-sm text-ink-3">Select a lesson from the curriculum to begin.</p>
          )}

          {activeLesson && !activeLesson.is_unlocked && (
            <div>
              <h2 className="mb-2 font-heading text-lg font-semibold text-ink">{activeLesson.title}</h2>
              <p className="text-sm text-ink-3">
                {activeLesson.unlocked_at
                  ? `This lesson unlocks on ${new Date(activeLesson.unlocked_at).toLocaleDateString()}.`
                  : "This lesson isn't available yet."}
              </p>
            </div>
          )}

          {isLoadingContent && <p className="text-sm text-ink-3">Loading…</p>}

          {activeLesson?.is_unlocked && content && content.id === activeLesson.id && (
            <div>
              <h2 className="mb-4 font-heading text-lg font-semibold text-ink">{content.title}</h2>
              {content.type === "text" && (
                <p className="whitespace-pre-wrap text-sm leading-relaxed text-ink-2">
                  {content.content_body}
                </p>
              )}
              {content.type === "external" && content.content_body && (
                <a
                  href={content.content_body}
                  target="_blank"
                  rel="noreferrer"
                  className="text-sm font-medium text-brand-primary hover:underline"
                >
                  Open external resource →
                </a>
              )}
              {(content.type === "document" || content.type === "video") && content.media && (
                <a
                  href={content.media.url}
                  target="_blank"
                  rel="noreferrer"
                  className="text-sm font-medium text-brand-primary hover:underline"
                >
                  {content.type === "video" ? "Play video" : "Open document"} →
                </a>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
