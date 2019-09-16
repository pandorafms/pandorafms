import ColorCloud, { colorCloudPropsDecoder } from "./ColorCloud";
import { itemMetaDecoder } from "../lib";

const genericRawProps = {
  id: 1,
  type: 20, // COlor cloud item = 20
  label: null,
  isLinkEnabled: false,
  isOnTop: false,
  parentId: null,
  aclGroupId: null
};

const positionRawProps = {
  x: 100,
  y: 50
};

const sizeRawProps = {
  width: 100,
  height: 100
};

const colorCloudProps = {
  color: "rgb(100, 50, 245)"
};

const linkedModuleProps = {
  // Agent props.
  agentId: null,
  agentName: null,
  // Module props.
  moduleId: null,
  moduleName: null
};

describe("Color cloud item", () => {
  const colorCloudInstance = new ColorCloud(
    colorCloudPropsDecoder({
      ...genericRawProps,
      ...positionRawProps,
      ...sizeRawProps,
      ...linkedModuleProps,
      ...colorCloudProps
    }),
    itemMetaDecoder({
      receivedAt: new Date(1)
    })
  );

  it("should throw when using an invalid color into the props decoder", () => {
    expect(() =>
      colorCloudPropsDecoder({
        ...genericRawProps,
        ...positionRawProps,
        ...sizeRawProps,
        ...linkedModuleProps,
        color: null
      })
    ).toThrowError(TypeError);
  });

  it("should have the color-cloud class", () => {
    expect(
      colorCloudInstance.elementRef.getElementsByClassName("color-cloud").length
    ).toBeGreaterThan(0);
  });
});
