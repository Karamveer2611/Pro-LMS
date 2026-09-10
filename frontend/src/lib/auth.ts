import { apiFetch } from "./api";

export type UserRole = "admin" | "instructor" | "learner";

export type User = {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  phone: string | null;
  status: string;
  created_at: string;
};

const TOKEN_KEY = "poshprofs_token";

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

function setToken(token: string) {
  window.localStorage.setItem(TOKEN_KEY, token);
}

function clearToken() {
  window.localStorage.removeItem(TOKEN_KEY);
}

export async function login(email: string, password: string): Promise<User> {
  const { user, token } = await apiFetch<{ user: User; token: string }>(
    "/api/v1/auth/login",
    { method: "POST", body: JSON.stringify({ email, password }) },
  );
  setToken(token);
  return user;
}

export async function register(data: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<User> {
  const { user, token } = await apiFetch<{ user: User; token: string }>(
    "/api/v1/auth/register",
    { method: "POST", body: JSON.stringify(data) },
  );
  setToken(token);
  return user;
}

export async function logout(): Promise<void> {
  const token = getToken();
  clearToken();
  if (token) {
    await apiFetch("/api/v1/auth/logout", { method: "POST", token });
  }
}

export async function fetchCurrentUser(): Promise<User | null> {
  const token = getToken();
  if (!token) return null;

  try {
    const { data } = await apiFetch<{ data: User }>("/api/v1/auth/me", { token });
    return data;
  } catch {
    clearToken();
    return null;
  }
}
