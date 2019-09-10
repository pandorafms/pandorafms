import Clock, { clockPropsDecoder } from ".";
import { itemMetaDecoder } from "../../lib";

const genericRawProps = {
  id: 1,
  type: 19, // Clock item = 19
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

const digitalClockProps = {
  clockType: "digital",
  clockFormat: "datetime",
  clockTimezone: "Madrid",
  clockTimezoneOffset: 60,
  showClockTimezone: true,
  color: "white"
};

const linkedModuleProps = {
  // Agent props.
  agentId: null,
  agentName: null,
  // Module props.
  moduleId: null,
  moduleName: null
};

describe("Clock item", () => {
  const clockInstance = new Clock(
    clockPropsDecoder({
      ...genericRawProps,
      ...positionRawProps,
      ...sizeRawProps,
      ...linkedModuleProps,
      ...digitalClockProps
    }),
    itemMetaDecoder({
      receivedAt: new Date(1)
    })
  );

  it("should have the digital-clock class", () => {
    expect(
      clockInstance.elementRef.getElementsByClassName("digital-clock").length
    ).toBeGreaterThan(0);
  });

  describe("getHumanTimezone function", () => {
    it("should return a better timezone", () => {
      expect(clockInstance.getHumanTimezone("America/New_York")).toBe(
        "New York"
      );
      expect(clockInstance.getHumanTimezone("Europe/Madrid")).toBe("Madrid");
      expect(clockInstance.getHumanTimezone("Asia/Tokyo")).toBe("Tokyo");
    });
  });
});
