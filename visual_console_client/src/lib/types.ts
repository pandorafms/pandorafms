export interface AnyObject {
  [key: string]: any; // eslint-disable-line @typescript-eslint/no-explicit-any
}

export interface UnknownObject {
  [key: string]: unknown;
}

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
  agentAlias: string | null;
  agentDescription: string | null;
  agentAddress: string | null;
}

export interface WithModuleProps extends WithAgentProps {
  moduleId: number | null;
  moduleName: string | null;
  moduleDescription: string | null;
}

export type LinkedVisualConsolePropsStatus =
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
    };
export type LinkedVisualConsoleProps = {
  metaconsoleId?: number | null;
  linkedLayoutId: number | null;
  linkedLayoutAgentId: number | null;
} & LinkedVisualConsolePropsStatus;

export interface ItemMeta {
  receivedAt: Date;
  error: Error | null;
  isFromCache: boolean;
  isFetching: boolean;
  isUpdating: boolean;
  editMode: boolean;
}
