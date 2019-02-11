export type UnknownObject = {
  [key: string]: any;
};

export interface Position {
  x: number;
  y: number;
}

export interface Size {
  width: number;
  height: number;
}

export interface WithAgentProps {
  metaconsoleId?: number | null;
  agentId: number | null;
  agentName: string | null;
}

export interface WithModuleProps extends WithAgentProps {
  moduleId: number | null;
  moduleName: string | null;
}

export type LinkedVisualConsoleProps = {
  metaconsoleId?: number | null;
  linkedLayoutId: number | null;
  linkedLayoutAgentId: number | null;
} & (
  | {
      linkedLayoutStatusType: "default";
    }
  | {
      linkedLayoutStatusType: "weight";
      linkedLayoutStatusTypeWeight: number;
    }
  | {
      linkedLayoutStatusType: "service";
      linkedLayoutStatusTypeWarningThreshold: number;
      linkedLayoutStatusTypeCriticalThreshold: number;
    });
