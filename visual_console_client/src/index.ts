/*
 * Useful resources.
 * http://es6-features.org/
 * http://exploringjs.com/es6
 * https://www.typescriptlang.org/
 */

import "./main.css"; // CSS import.
import VisualConsole from "./VisualConsole";
import AsyncTaskManager from "./lib/AsyncTaskManager";

// Export the VisualConsole class to the global object.
// eslint-disable-next-line
(window as any).VisualConsole = VisualConsole;

// Export the AsyncTaskManager class to the global object.
// eslint-disable-next-line
(window as any).AsyncTaskManager = AsyncTaskManager;
