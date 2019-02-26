import Clock, { clockPropsDecoder } from "./Clock";

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
  clockTimezoneOffset: 60
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
    })
  );

  it("should have the digital-clock class", () => {
    expect(
      clockInstance.elementRef.getElementsByClassName("digital-clock").length
    ).toBeGreaterThan(0);
  });

  describe("getDate function", () => {
    it("should return the date with padded 0's", () => {
      const expected = "01/02/0123";
      const date = new Date(`02/01/0123 12:00:00`);
      const digitalDate = clockInstance.getDigitalDate(date);
      expect(digitalDate).toBe(expected);
    });
  });

  describe("getTime function", () => {
    it("should return the time with padded 0's when hours/minutes/seconds are less than 10", () => {
      const expected = "01:02:03";
      const date = new Date(`01/01/1970 ${expected}`);
      const digitalTime = clockInstance.getDigitalTime(date);
      expect(digitalTime).toBe(expected);
    });
  });
});
