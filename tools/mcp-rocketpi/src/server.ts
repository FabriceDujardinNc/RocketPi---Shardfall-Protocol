#!/usr/bin/env node
// Serveur MCP exposant le pipeline 3D RocketPi à Claude Desktop.
//
// Conçu pour être lancé via stdin/stdout par Claude Desktop. Configurer
// claude_desktop_config.json :
//
//   {
//     "mcpServers": {
//       "rocketpi": {
//         "command": "node",
//         "args": ["<absolute path>/tools/mcp-rocketpi/dist/server.js"],
//         "env": {
//           "ROCKETPI_API_BASE_URL": "https://rocketpi.pro",
//           "ROCKETPI_API_TOKEN": "<sanctum token with mcp:read [+mcp:write]>"
//         }
//       }
//     }
//   }

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";

import { RocketpiClient, RocketpiApiError } from "./rocketpi-client.js";

// ─── Bootstrap ─────────────────────────────────────────────────────────

const baseUrl = process.env.ROCKETPI_API_BASE_URL;
const token = process.env.ROCKETPI_API_TOKEN;

if (!baseUrl || !token) {
  process.stderr.write(
    "[mcp-rocketpi] missing ROCKETPI_API_BASE_URL or ROCKETPI_API_TOKEN env vars — refusing to start\n",
  );
  process.exit(1);
}

const client = new RocketpiClient({ baseUrl, token });

const server = new McpServer({
  name: "rocketpi",
  version: "0.1.0",
});

// Helper : convertit n'importe quelle réponse en { content: [{type: "text", text: JSON}] }
function jsonResult(payload: unknown) {
  return {
    content: [
      {
        type: "text" as const,
        text: JSON.stringify(payload, null, 2),
      },
    ],
  };
}

function errorResult(e: unknown) {
  const message =
    e instanceof RocketpiApiError
      ? `${e.message}\n${JSON.stringify(e.body, null, 2)}`
      : e instanceof Error
        ? e.message
        : String(e);

  return {
    isError: true,
    content: [{ type: "text" as const, text: message }],
  };
}

// ─── Tools ─────────────────────────────────────────────────────────────

server.tool(
  "list_operators",
  "List all operators in the RocketPi roster with their 3D base mesh status. " +
    "Returns id, slug, codename, name, faction, role, rarity, and base generation status.",
  {},
  async () => {
    try {
      const data = await client.get("/api/asset3d/operators");
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

server.tool(
  "get_operator",
  "Get full details for one operator: 3D base mesh status, skins (filtered active), " +
    "and accessories attached. Accepts the slug (e.g. 'vex') or codename (e.g. 'VX-01').",
  {
    operator: z.string().describe("Operator slug or codename"),
  },
  async ({ operator }) => {
    try {
      const data = await client.get(`/api/asset3d/operators/${encodeURIComponent(operator)}`);
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

server.tool(
  "list_operator_skins",
  "List all active skins for a specific operator, with texture URLs and generation status.",
  {
    operator: z.string().describe("Operator slug or codename"),
  },
  async ({ operator }) => {
    try {
      const data = await client.get(
        `/api/asset3d/operators/${encodeURIComponent(operator)}/skins`,
      );
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

server.tool(
  "list_weapons",
  "List all weapons. Optionally filter by category (assault, sniper, shotgun, smg, " +
    "pistol, launcher, melee). Includes skins eagerly loaded.",
  {
    category: z
      .enum(["assault", "sniper", "shotgun", "smg", "pistol", "launcher", "melee"])
      .optional()
      .describe("Optional category filter"),
  },
  async ({ category }) => {
    try {
      const qs = category ? `?category=${encodeURIComponent(category)}` : "";
      const data = await client.get(`/api/asset3d/weapons${qs}`);
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

server.tool(
  "list_accessories",
  "List all accessories. Optionally filter by slot (head, face, back, hands, legs).",
  {
    slot: z
      .enum(["head", "face", "back", "hands", "legs"])
      .optional()
      .describe("Optional slot filter"),
  },
  async ({ slot }) => {
    try {
      const qs = slot ? `?slot=${encodeURIComponent(slot)}` : "";
      const data = await client.get(`/api/asset3d/accessories${qs}`);
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

server.tool(
  "get_generation_status",
  "Check the current Meshy.ai generation status for a specific entity " +
    "(pending / queued / generating / ready / failed).",
  {
    entity_type: z
      .enum(["operator", "operator_skin", "weapon", "accessory"])
      .describe("Entity type"),
    slug: z.string().describe("Entity slug"),
  },
  async ({ entity_type, slug }) => {
    try {
      const data = await client.get(
        `/api/asset3d/status/${encodeURIComponent(entity_type)}/${encodeURIComponent(slug)}`,
      );
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

server.tool(
  "trigger_generation",
  "Trigger a Meshy.ai 3D generation for an entity (operator base mesh, skin, weapon, " +
    "or accessory). Returns the Meshy task ID immediately; the job runs asynchronously " +
    "in the Laravel queue. Requires the API token to carry the mcp:write ability.",
  {
    entity_type: z
      .enum(["operator", "operator_skin", "weapon", "accessory"])
      .describe("Entity type"),
    slug: z.string().describe("Entity slug"),
    force: z
      .boolean()
      .optional()
      .describe("If true, re-runs even when the entity is already 'ready'. Costs an API credit."),
  },
  async ({ entity_type, slug, force }) => {
    try {
      const data = await client.post("/api/asset3d/generate", {
        entity_type,
        slug,
        force: force ?? false,
      });
      return jsonResult(data);
    } catch (e) {
      return errorResult(e);
    }
  },
);

// ─── Run ────────────────────────────────────────────────────────────────

const transport = new StdioServerTransport();
await server.connect(transport);

process.stderr.write("[mcp-rocketpi] connected via stdio\n");
