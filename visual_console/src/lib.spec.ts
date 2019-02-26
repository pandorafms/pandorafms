import { parseIntOr, padLeft } from "./lib";

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
