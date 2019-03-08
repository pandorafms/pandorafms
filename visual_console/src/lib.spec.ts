import { parseIntOr, notEmptyStringOr, padLeft, prefixedCssRules } from "./lib";

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
