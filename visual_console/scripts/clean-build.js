// eslint-disable-next-line @typescript-eslint/no-var-requires
const path = require("path");
// eslint-disable-next-line @typescript-eslint/no-var-requires
const fs = require("fs");

const buildPath = path.join(
  __dirname,
  "..",
  "..",
  process.env.BUILD_PATH && process.env.BUILD_PATH.length > 0
    ? process.env.BUILD_PATH
    : "build"
);

if (fs.existsSync(buildPath)) {
  fs.readdirSync(buildPath).forEach(file =>
    fs.unlinkSync(path.join(buildPath, file))
  );
}
