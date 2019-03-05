const path = require("path");
const fs = require("fs");

const buildPath = path.join(__dirname, "..", "build");

if (fs.existsSync(buildPath)) {
  fs.readdirSync(buildPath).forEach(file =>
    fs.unlinkSync(path.join(buildPath, file))
  );
}
