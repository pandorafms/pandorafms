import { IconDefinition } from "@fortawesome/free-solid-svg-icons";
import "./FontAwesomeIcon.styles.css";

const svgNS = "http://www.w3.org/2000/svg";

const fontAwesomeIcon = (
  iconDefinition: IconDefinition,
  {
    size,
    color,
    spin
  }: {
    size?: "small" | "medium" | "large";
    color?: string;
    spin?: boolean;
  }
): HTMLElement => {
  const container = document.createElement("figure");
  container.className = `fa fa-${iconDefinition.iconName}`;
  if (size) container.classList.add(`fa-${size}`);
  if (spin) container.classList.add("fa-spin");

  const icon = document.createElementNS(svgNS, "svg");
  // Auto resize SVG using the view box magic: https://css-tricks.com/scale-svg/
  icon.setAttribute(
    "viewBox",
    `0 0 ${iconDefinition.icon[0]} ${iconDefinition.icon[1]}`
  );
  if (color) icon.setAttribute("fill", color);

  // Path
  const path = document.createElementNS(svgNS, "path");
  const pathData =
    typeof iconDefinition.icon[4] === "string"
      ? iconDefinition.icon[4]
      : iconDefinition.icon[4][0];
  path.setAttribute("d", pathData);

  icon.appendChild(path);
  container.appendChild(icon);

  return container;
};

export default fontAwesomeIcon;
