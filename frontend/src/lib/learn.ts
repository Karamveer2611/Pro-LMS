import { apiFetch } from "./api";
import { getToken } from "./auth";

export type LessonType = "video" | "document" | "text" | "external";

export type CurriculumLesson = {
  id: number;
  title: string;
  type: LessonType;
  order: number;
  is_published: boolean;
  is_preview: boolean;
  is_required: boolean;
  release_type: string;
  is_unlocked: boolean | null;
  unlocked_at: string | null;
};

export type CurriculumSection = {
  id: number;
  title: string;
  order: number;
  lessons: CurriculumLesson[];
};

export type CurriculumModule = {
  id: number;
  title: string;
  order: number;
  sections: CurriculumSection[];
};

export type CourseWithCurriculum = {
  id: number;
  title: string;
  short_description: string | null;
  modules: CurriculumModule[];
};

export type LessonContent = {
  id: number;
  title: string;
  type: LessonType;
  content_body: string | null;
  media: { url: string; mime_type: string } | null;
};

export async function getCourseCurriculum(courseId: number): Promise<CourseWithCurriculum> {
  const { data } = await apiFetch<{ data: CourseWithCurriculum }>(
    `/api/v1/courses/${courseId}/curriculum`,
    { token: getToken() },
  );
  return data;
}

export async function getLessonContent(lessonId: number): Promise<LessonContent> {
  const { data } = await apiFetch<{ data: LessonContent }>(
    `/api/v1/lessons/${lessonId}/content`,
    { token: getToken() },
  );
  return data;
}
