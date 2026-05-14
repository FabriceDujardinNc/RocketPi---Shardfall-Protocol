// Client HTTP minimal pour l'API Laravel RocketPi.
// Concrètement, on appelle les endpoints sous /api/asset3d/* documentés
// dans routes/api.php. Tous les calls sont authentifiés via un token
// Sanctum (Bearer) fourni par variable d'env ROCKETPI_API_TOKEN.

export interface RocketpiClientOptions {
  baseUrl: string;
  token: string;
}

type Json = Record<string, unknown> | unknown[];

export class RocketpiClient {
  private readonly baseUrl: string;
  private readonly token: string;

  constructor(opts: RocketpiClientOptions) {
    this.baseUrl = opts.baseUrl.replace(/\/$/, "");
    this.token = opts.token;
  }

  async get<T = Json>(path: string): Promise<T> {
    return this.request<T>("GET", path);
  }

  async post<T = Json>(path: string, body: unknown): Promise<T> {
    return this.request<T>("POST", path, body);
  }

  private async request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const url = `${this.baseUrl}${path.startsWith("/") ? path : `/${path}`}`;

    const response = await fetch(url, {
      method,
      headers: {
        Authorization: `Bearer ${this.token}`,
        Accept: "application/json",
        ...(body !== undefined ? { "Content-Type": "application/json" } : {}),
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
      let errorBody: unknown = null;
      try {
        errorBody = await response.json();
      } catch {
        errorBody = await response.text();
      }
      throw new RocketpiApiError(
        `RocketPi API ${method} ${path} failed: ${response.status} ${response.statusText}`,
        response.status,
        errorBody,
      );
    }

    return (await response.json()) as T;
  }
}

export class RocketpiApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly body: unknown,
  ) {
    super(message);
    this.name = "RocketpiApiError";
  }
}
