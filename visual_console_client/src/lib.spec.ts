import {
  parseIntOr,
  stringIsEmpty,
  notEmptyStringOr,
  padLeft,
  prefixedCssRules,
  decodeBase64,
  humanDate,
  humanTime
} from "./lib";

describe("function parseIntOr", () => {
  it("should retrieve valid int or a default value", () => {
    expect(parseIntOr("Foo", null)).toBe(null);
    expect(parseIntOr("1a", null)).toBe(1);
    expect(parseIntOr("a1", null)).toBe(null);
    expect(parseIntOr("1", null)).toBe(1);
    expect(parseIntOr(false, null)).toBe(null);
    expect(parseIntOr(true, null)).toBe(null);
    expect(parseIntOr(1, null)).toBe(1);
  });
});

describe("function stringIsEmpty", () => {
  it("should check properly if a string is empry or not", () => {
    expect(stringIsEmpty()).toBe(true);
    expect(stringIsEmpty("")).toBe(true);
    expect(stringIsEmpty("foo")).toBe(false);
    expect(stringIsEmpty("bar")).toBe(false);
  });
});

describe("function notEmptyStringOr", () => {
  it("should retrieve not empty string or a default value", () => {
    expect(notEmptyStringOr("", null)).toBe(null);
    expect(notEmptyStringOr("Foo", null)).toBe("Foo");
    expect(notEmptyStringOr(1, 1)).toBe(1);
    expect(notEmptyStringOr(1, 0)).toBe(0);
    expect(notEmptyStringOr("", 0)).toBe(0);
    expect(notEmptyStringOr("Foo", "Bar")).toBe("Foo");
    expect(notEmptyStringOr(0, "Bar")).toBe("Bar");
  });
});

describe("function padLeft", () => {
  it("should pad properly", () => {
    expect(padLeft(1, 2, 0)).toBe("01");
    expect(padLeft(1, 4, 0)).toBe("0001");
    expect(padLeft(1, 4, "0")).toBe("0001");
    expect(padLeft("1", 4, "0")).toBe("0001");
    expect(padLeft(10, 4, 0)).toBe("0010");
    expect(padLeft("bar", 6, "foo")).toBe("foobar");
    expect(padLeft("bar", 11, "foo")).toBe("foofoofobar");
    expect(padLeft("bar", 4, "foo")).toBe("fbar");
    expect(padLeft("bar", 2, "foo")).toBe("ar");
    expect(padLeft("bar", 3, "foo")).toBe("bar");
  });
});

describe("function prefixedCssRules", () => {
  it("should add the prefixes to the rules", () => {
    const rules = prefixedCssRules("transform", "rotate(0)");
    expect(rules).toContainEqual("transform: rotate(0);");
    expect(rules).toContainEqual("-webkit-transform: rotate(0);");
    expect(rules).toContainEqual("-moz-transform: rotate(0);");
    expect(rules).toContainEqual("-ms-transform: rotate(0);");
    expect(rules).toContainEqual("-o-transform: rotate(0);");
    expect(rules).toHaveLength(5);
  });
});

describe("function decodeBase64", () => {
  it("should decode the base64 without errors", () => {
    expect(decodeBase64("SGkgSSdtIGRlY29kZWQ=")).toEqual("Hi I'm decoded");
    expect(decodeBase64("Rk9PQkFSQkFa")).toEqual("FOOBARBAZ");
    expect(decodeBase64("eyJpZCI6MSwibmFtZSI6ImZvbyJ9")).toEqual(
      '{"id":1,"name":"foo"}'
    );
    expect(
      decodeBase64("PGRpdj5Cb3ggPHA+UGFyYWdyYXBoPC9wPjxociAvPjwvZGl2Pg==")
    ).toEqual("<div>Box <p>Paragraph</p><hr /></div>");
  });
});

describe("humanDate function", () => {
  it("should return the date with padded 0's", () => {
    const expected = "01/02/0123";
    const date = new Date(`02/01/0123 12:00:00`);
    const digitalDate = humanDate(date);
    expect(digitalDate).toBe(expected);
  });
});

describe("humanTime function", () => {
  it("should return the time with padded 0's when hours/minutes/seconds are less than 10", () => {
    const expected = "01:02:03";
    const date = new Date(`01/01/1970 ${expected}`);
    const digitalTime = humanTime(date);
    expect(digitalTime).toBe(expected);
  });
});
