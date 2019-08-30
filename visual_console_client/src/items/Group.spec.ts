import Group, { groupPropsDecoder } from "./Group";
import { itemMetaDecoder } from "../lib";

const genericRawProps = {
  id: 1,
  type: 11, // Group item = 11
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

const groupRawProps = {
  imageSrc:
    "https://brutus.artica.lan:8081/uploads/-/system/project/avatar/1/1.png",
  groupId: 1
};

describe("Group item", () => {
  const groupInstance = new Group(
    groupPropsDecoder({
      ...genericRawProps,
      ...positionRawProps,
      ...sizeRawProps,
      ...groupRawProps
    }),
    itemMetaDecoder({
      receivedAt: new Date(1)
    })
  );

  it("should have the group class", () => {
    expect(
      groupInstance.elementRef.getElementsByClassName("group").length
    ).toBeGreaterThan(0);
  });
});
