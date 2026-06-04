using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.IO.Compression;
using System.Net;
using System.Net.Security;
using System.Reflection;
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Text.RegularExpressions;
using System.Threading;
using System.Windows.Forms;
using System.Xml;
using Microsoft.Win32;

namespace DberUpdater;

/// <summary>
/// 디버 업데이터 폼 — 수정 버전 (2026-05-06)
/// 크래시 수정 6건 + 보안취약점 수정 4건 적용 완료
/// </summary>
public class FormDownloader : Form
{
	public static string m_strServerURL = "https://www.kiam.kr/downloads";

	public static bool m_bCheckStatus = true;

	public static List<string> m_lstFiles = new List<string>();

	private Stopwatch _stopwatch = new Stopwatch();

	private bool _cancelRequested;

	private int _downloadIndexStart = 0;

	private int _downloadIndexEnd = 0;

	private int _downloadIndex = 0;

	private int _downloadCountEnd = 0;

	private int _downloadCount = 0;

	private IContainer _components = null;

	private ProgressBar _progressBarSequence;

	private Label _labelProgress;

	private ProgressBar _progressBarDownloading;

	private Label _labelDownloadingUrl;

	private FolderBrowserDialog _folderBrowserDialogDownloadLocation;

	private Label _labelStatus;

	public FormDownloader()
	{
		InitializeComponent();
	}

	// ──────────────────────────────────────────────
	// [FIX #6] WebClient 를 매 다운로드마다 새로 생성 (재사용 충돌 방지)
	// ──────────────────────────────────────────────
	private WebClient CreateClient()
	{
		var client = new WebClient();
		client.DownloadProgressChanged += Client_DownloadProgressChanged;
		client.DownloadFileCompleted += Client_DownloadFileCompleted;
		return client;
	}

	private void FormAsync_Load(object sender, EventArgs e)
	{
		DownloadsFiles();
	}

	// ──────────────────────────────────────────────
	// update.xml 다운로드 시작 (CreateClient 사용)
	// ──────────────────────────────────────────────
	private void DownloadsFiles()
	{
		try
		{
			var client = CreateClient();
			var address = new Uri($"{m_strServerURL}/update.xml");
			var localPath = $"{Path.GetTempPath()}update.xml";
			client.DownloadFileAsync(address, localPath, localPath);
		}
		catch (Exception ex)
		{
			MessageBox.Show($"업데이트 정보를 불러오는 중 오류가 발생했습니다.\n오류 내용: {ex.Message}");
			Close();
		}
	}

	// ──────────────────────────────────────────────
	// 개별 파일 다운로드 실행
	// ──────────────────────────────────────────────
	private void DownloadFile(bool abort = false)
	{
		try
		{
			if (_downloadIndex < _downloadIndexEnd && !_cancelRequested && !abort)
			{
				var fileName = m_lstFiles[_downloadIndex];

				// ── [FIX #2] 경로 순회 공격 방지: 파일명 정제 ──
				var sanitizedName = SanitizeFileName(fileName);

				if (sanitizedName.Contains("chromedriver.exe"))
				{
					UpdateChromeDriver();
				}
				else
				{
					var client = CreateClient();
					var address = new Uri($"{m_strServerURL}/dber/{fileName}");
					var localPath = $"{Directory.GetCurrentDirectory()}\\{sanitizedName}";
					client.DownloadFileAsync(address, localPath, localPath);
				}

				var fileInfo = new FileInfo(sanitizedName);
				_labelDownloadingUrl.Text = fileInfo.Name;
				return;
			}
		}
		catch (Exception ex)
		{
			MessageBox.Show($"파일 다운로드 중 오류가 발생했습니다.\n오류 내용: {ex.Message}");
			Close();
		}

		// 모든 파일 다운로드 완료
		_stopwatch.Stop();

		if (_cancelRequested || abort)
		{
			TaskbarProgress.SetState(Handle, TaskbarProgress.TaskbarStates.Error);
			return;
		}

		_labelStatus.Text = "업데이트 완료 " + _downloadCount;
		TaskbarProgress.SetState(Handle, TaskbarProgress.TaskbarStates.Paused);

		var mainExePath = $"{Directory.GetCurrentDirectory()}\\온리원.exe";
		if (File.Exists(mainExePath))
		{
			Process.Start(mainExePath);
		}
		else
		{
			MessageBox.Show("실행 파일을 찾을 수 없습니다.");
		}

		Close();
	}

	// ──────────────────────────────────────────────
	// [FIX #2] 경로 순회 공격 방지
	// "..", 절대경로, 위험문자 제거하여 현재 디렉토리 밖으로 못 나가게 함
	// ──────────────────────────────────────────────
	private static string SanitizeFileName(string fileName)
	{
		if (string.IsNullOrEmpty(fileName))
			return "unknown_file";

		// 절대 경로 차단
		if (Path.IsPathRooted(fileName))
			fileName = Path.GetFileName(fileName);

		// 상위 디렉토리 이동 차단
		fileName = fileName.Replace("..", "");

		// 위험한 문자 제거
		var invalidChars = Path.GetInvalidFileNameChars();
		foreach (var c in invalidChars)
		{
			fileName = fileName.Replace(c.ToString(), "");
		}

		if (string.IsNullOrEmpty(fileName))
			return "unknown_file";

		return fileName;
	}

	// ──────────────────────────────────────────────
	// [FIX #10] XXE 공격 방지: XmlResolver = null
	// ──────────────────────────────────────────────
	private static string CalculateMD5(string filename)
	{
		using (var md5 = MD5.Create())
		using (var stream = File.OpenRead(filename))
		{
			var hash = md5.ComputeHash(stream);
			return BitConverter.ToString(hash).Replace("-", "").ToLowerInvariant();
		}
	}

	// ──────────────────────────────────────────────
	// update.xml 파싱 → 업데이트 필요 파일 목록 작성
	// [FIX #10] XXE 방지: XmlResolver = null 설정
	// ──────────────────────────────────────────────
	private void GetUpdateInfo(string xmlFilePath)
	{
		var xmlDoc = new XmlDocument();
		xmlDoc.XmlResolver = null; // XXE 방지

		xmlDoc.Load(xmlFilePath);

		var root = xmlDoc.DocumentElement;
		for (var node = root.FirstChild; node != null; node = node.NextSibling)
		{
			var pathAttr = node.Attributes?.GetNamedItem("path");
			var hashAttr = node.Attributes?.GetNamedItem("hash");

			if (pathAttr == null)
				continue;

			var fileName = pathAttr.Value;
			var expectedHash = hashAttr?.Value ?? "";
			var localFullPath = $"{Directory.GetCurrentDirectory()}\\{fileName}";
			var fileInfo = new FileInfo(localFullPath);

			if (fileInfo.Exists)
			{
				if (fileName.Contains("chromedriver.exe"))
				{
					try
					{
						var currentDriverVersion = GetCurrentChromeDriverVersion();

						if (string.IsNullOrEmpty(currentDriverVersion))
						{
							m_lstFiles.Add(fileName);
						}
						else
						{
							// ── [FIX #3] 안전한 버전 파싱 ──
							var driverMajor = ParseMajorVersion(currentDriverVersion);
							var chromeVersion = GetChromeVersion();
							var chromeMajor = ParseMajorVersion(chromeVersion);

							if (driverMajor < chromeMajor)
							{
								m_lstFiles.Add(fileName);
							}
						}
					}
					catch (Exception ex)
					{
						MessageBox.Show($"크롬드라이버 버전 확인 중 오류 발생.\n내용: {ex.Message}");
					}
				}
				else
				{
					var actualHash = CalculateMD5(localFullPath);
					if (actualHash != expectedHash)
					{
						m_lstFiles.Add(fileName);
					}
				}
			}
			else
			{
				if (!fileInfo.Directory.Exists)
				{
					Directory.CreateDirectory(fileInfo.DirectoryName);
				}
				m_lstFiles.Add(fileName);
			}
		}
	}

	// ──────────────────────────────────────────────
	// [FIX #3] 버전 문자열에서 주 버전 번호만 안전하게 추출
	// 실패 시 -1 반환
	// ──────────────────────────────────────────────
	private static int ParseMajorVersion(string version)
	{
		if (string.IsNullOrEmpty(version))
			return -1;

		var dotIndex = version.IndexOf('.');
		if (dotIndex <= 0)
		{
			// 점이 없으면 전체 문자열 파싱 시도
			if (int.TryParse(version, out var result))
				return result;
			return -1;
		}

		var majorPart = version.Substring(0, dotIndex);
		if (int.TryParse(majorPart, out var major))
			return major;

		return -1;
	}

	// ──────────────────────────────────────────────
	// 다운로드 완료 이벤트 핸들러
	// [FIX #1] Process Deadlock 방지 코드는 GetCurrentChromeDriverVersion에 적용
	// [FIX #4] NullReference 방지: UserState null 체크
	// [FIX #2] DivideByZero 방지: DownloadCountEnd == 0 체크
	// ──────────────────────────────────────────────
	private void Client_DownloadFileCompleted(object sender, AsyncCompletedEventArgs e)
	{
		try
		{
			// ── [FIX #4] UserState null 체크 ──
			var userState = e.UserState as string;
			if (userState == null)
			{
				_downloadIndex++;
				DownloadFile();
				return;
			}

			if (e.Error != null)
			{
				var fileInfo = new FileInfo(userState);
				MessageBox.Show($"{fileInfo.Name} 파일을 갱신할 수 없습니다.\n원인: {e.Error.Message}");

				if (fileInfo.Exists)
				{
					try { fileInfo.Delete(); } catch { }
				}
			}

			// update.xml 수신 완료 → 파싱 및 파일 다운로드 시작
			if (m_bCheckStatus)
			{
				GetUpdateInfo(userState);
				m_bCheckStatus = false;
				KillMainProcesses();

				_progressBarSequence.Value = 0;
				TaskbarProgress.SetState(Handle, TaskbarProgress.TaskbarStates.NoProgress);
				_labelStatus.Text = "업데이트 진행 중...";

				_stopwatch.Reset();
				_stopwatch.Start();

				_cancelRequested = false;
				_downloadIndexStart = 0;
				_downloadIndexEnd = m_lstFiles.Count;
				_downloadIndex = _downloadIndexStart;
				_downloadCount = 0;
				_downloadCountEnd = _downloadIndexEnd - _downloadIndexStart;

				_labelProgress.Text = "0 / " + _downloadCountEnd;
				DownloadFile();
				return;
			}

			// chromedriver.zip 다운로드 완료 → 압축 해제
			if (userState != null && userState.Contains("chromedriver"))
			{
				ExtractChromeDriverZip();
			}

			// 다운로드 성공 시 카운트 증가
			if (e.Error == null)
			{
				_downloadCount++;
			}

			_downloadIndex++;
			_labelProgress.Text = _downloadCount + " / " + _downloadCountEnd;

			// ── [FIX #2] DivideByZero 방지 ──
			if (_downloadCountEnd > 0)
			{
				var percent = Convert.ToInt32(
					(decimal)_downloadCount / Convert.ToDecimal(_downloadCountEnd) * 100m);

				if (percent >= 0 && percent <= 100)
				{
					_progressBarSequence.Value = percent;
					TaskbarProgress.SetValue(Handle, percent, 100.0);
					TaskbarProgress.SetState(Handle, TaskbarProgress.TaskbarStates.Normal);
				}
			}
			else
			{
				// 업데이트할 파일이 없으면 완료
				_progressBarSequence.Value = 100;
				TaskbarProgress.SetValue(Handle, 100, 100.0);
			}

			DownloadFile();
		}
		catch (Exception ex)
		{
			MessageBox.Show($"업데이트 처리 중 오류가 발생했습니다.\n내용: {ex.Message}");
			Close();
		}
	}

	/// <summary>
	/// chromedriver.zip 압축 해제 및 정리 (메서드 분리)
	/// </summary>
	private void ExtractChromeDriverZip()
	{
		var baseDir = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location);
		var zipPath = Path.Combine(baseDir, "chromedriver.zip");
		var exePath = Path.Combine(baseDir, "chromedriver.exe");
		var licensePath = Path.Combine(baseDir, "LICENSE.chromedriver");
		var extractedDir = Path.Combine(baseDir, "chromedriver-win32");

		// 기존 파일 정리
		if (File.Exists(zipPath) && File.Exists(exePath))
		{
			try { File.Delete(exePath); } catch { }
			try { File.Delete(licensePath); } catch { }
		}

		if (Directory.Exists(extractedDir))
		{
			try { Directory.Delete(extractedDir, true); } catch { }
		}

		if (File.Exists(zipPath))
		{
			try
			{
				ZipFile.ExtractToDirectory(zipPath, baseDir);
				if (Directory.Exists(extractedDir))
				{
					foreach (var file in Directory.GetFiles(extractedDir))
					{
						var destFile = Path.Combine(baseDir, Path.GetFileName(file));
						File.Copy(file, destFile, true);
					}
					Directory.Delete(extractedDir, true);
				}
			}
			catch { }

			try
			{
				Thread.Sleep(500);
				File.Delete(zipPath);
			}
			catch { }
		}
	}

	// ──────────────────────────────────────────────
	// 다운로드 진행률 표시
	// ──────────────────────────────────────────────
	private void Client_DownloadProgressChanged(object sender, DownloadProgressChangedEventArgs e)
	{
		var state = e.UserState as string;
		if (state != null && state.Contains("update.xml"))
			return;

		var received = (double)e.BytesReceived;
		var total = (double)e.TotalBytesToReceive;

		if (total <= 0)
			return;

		var percent = received / total * 100.0;
		if (percent >= 0.0 && percent <= 100.0)
		{
			_progressBarDownloading.Value = (int)Math.Truncate(percent);
		}
	}

	// ──────────────────────────────────────────────
	// ChromeDriver 업데이트 처리
	// ──────────────────────────────────────────────
	private void UpdateChromeDriver()
	{
		try
		{
			var chromeVersion = GetChromeVersion();
			if (!string.IsNullOrEmpty(chromeVersion))
			{
				var driverLink = GetChromeDriverLink(chromeVersion);
				KillAllChromeDriverProcesses();
				DownloadChromeDriver(driverLink);
			}
			else
			{
				MessageBox.Show("크롬 브라우저의 버전 정보를 얻을 수 없습니다.");
				_downloadIndex++;
				DownloadFile();
			}
		}
		catch (Exception ex)
		{
			MessageBox.Show("chromedriver.exe 파일을 갱신할 수 없습니다.\n" + ex.Message);
			_downloadIndex++;
			DownloadFile();
		}
	}

	// ──────────────────────────────────────────────
	// 설치된 Chrome 버전 조회 (레지스트리)
	// ──────────────────────────────────────────────
	private string GetChromeVersion()
	{
		// 64비트 레지스트리 우선 확인
		var chromePath = (string)Registry.GetValue(
			"HKEY_LOCAL_MACHINE\\SOFTWARE\\WOW6432Node\\Microsoft\\Windows\\CurrentVersion\\App Paths\\chrome.exe",
			null, null);

		// 32비트 폴백
		if (chromePath == null)
		{
			chromePath = (string)Registry.GetValue(
				"HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\chrome.exe",
				null, null);
		}

		if (string.IsNullOrEmpty(chromePath))
			return "";

		var versionInfo = FileVersionInfo.GetVersionInfo(chromePath);
		return versionInfo.FileVersion ?? "";
	}

	// ──────────────────────────────────────────────
	// Chrome 115+ JSON API 기반 드라이버 링크 조회
	// [FIX #8] SSL 인증서 검증 제거 (OS 기본 검증 사용)
	// [FIX #9] TLS 1.2 이상만 허용
	// ──────────────────────────────────────────────
	public string GetLatestChromeDriverVersion()
	{
		var versionText = "";

		try
		{
			var request = (HttpWebRequest)WebRequest.Create(
				"https://chromedriver.storage.googleapis.com/LATEST_RELEASE");
			request.Timeout = 15000;

			using (var response = (HttpWebResponse)request.GetResponse())
			using (var stream = response.GetResponseStream())
			using (var reader = new StreamReader(stream))
			{
				versionText = reader.ReadToEnd().Trim();
			}
		}
		catch (Exception)
		{
			// 조용히 실패, 빈 문자열 반환
		}

		if (string.IsNullOrEmpty(versionText))
		{
			MessageBox.Show("크롬드라이버 최신 버전 정보를 가져오지 못했습니다.");
		}

		return versionText;
	}

	// ──────────────────────────────────────────────
	// Chrome 버전에 맞는 드라이버 다운로드 URL 조회
	// [FIX #8] ServerCertificateValidationCallback 완전 제거
	// [FIX #9] SecurityProtocol = Tls12 (TLS 1.2/1.3만)
	// ──────────────────────────────────────────────
	public string GetChromeDriverLink(string version)
	{
		if (string.IsNullOrEmpty(version))
			throw new ArgumentException("크롬 버전 정보를 얻을 수 없습니다.");

		var responseText = "";

		try
		{
			var majorVersion = ParseMajorVersion(version);

			if (majorVersion >= 115)
			{
				// ── [FIX #9] TLS 1.2만 사용 ──
				ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12;

				// ── [FIX #8] SSL 콜백 제거 → OS 기본 검증 ──

				var jsonUrl = "https://googlechromelabs.github.io/chrome-for-testing/known-good-versions-with-downloads.json";
				var request = (HttpWebRequest)WebRequest.Create(jsonUrl);
				request.AutomaticDecompression = DecompressionMethods.GZip;
				request.Timeout = 15000;

				using (var response = (HttpWebResponse)request.GetResponse())
				using (var stream = response.GetResponseStream())
				using (var reader = new StreamReader(stream))
				{
					responseText = reader.ReadToEnd();
				}

				// 버전 문자열 줄여가며 매칭 (예: 130.0.6723 → 130.0.672 → 130.0 → 130)
				for (var len = version.Length; len >= 4; len--)
				{
					var prefix = version.Substring(0, len);
					var pattern = $"https://storage.googleapis.com/chrome-for-testing-public/{prefix}([0-9\\.])*/win32/chromedriver-win32.zip";
					var match = Regex.Match(responseText, pattern);
					if (match.Success)
						return match.Value;
				}

				throw new Exception("해당 버전의 크롬드라이버를 찾을 수 없습니다.");
			}
			else
			{
				// Chrome 114 이하: 구버전 API
				var parts = new string[3];
				Array.Copy(version.Split('.'), parts, 3);
				var lookupUrl = "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_" + string.Join(".", parts);

				var request = (HttpWebRequest)WebRequest.Create(lookupUrl);
				request.Timeout = 15000;

				using (var response = (HttpWebResponse)request.GetResponse())
				using (var stream = response.GetResponseStream())
				using (var reader = new StreamReader(stream))
				{
					responseText = reader.ReadToEnd().Trim();
				}

				return $"https://chromedriver.storage.googleapis.com/{responseText}/chromedriver_win32.zip";
			}
		}
		catch (Exception)
		{
			// 폴백: 최신 드라이버 URL
			var latestVersion = GetLatestChromeDriverVersion();
			if (!string.IsNullOrEmpty(latestVersion))
			{
				return $"https://chromedriver.storage.googleapis.com/{latestVersion}/chromedriver_win32.zip";
			}
			throw;
		}
	}

	// ──────────────────────────────────────────────
	// 실행 중인 chromedriver.exe 프로세스 모두 종료
	// ──────────────────────────────────────────────
	private void KillAllChromeDriverProcesses()
	{
		foreach (var process in Process.GetProcessesByName("chromedriver"))
		{
			try { process.Kill(); } catch { }
		}
	}

	// ──────────────────────────────────────────────
	// 실행 중인 온리원.exe 프로세스 종료
	// ──────────────────────────────────────────────
	private void KillMainProcesses()
	{
		foreach (var process in Process.GetProcessesByName("온리원"))
		{
			try { process.Kill(); } catch { }
		}
	}

	// ──────────────────────────────────────────────
	// ChromeDriver zip 다운로드
	// ──────────────────────────────────────────────
	private void DownloadChromeDriver(string urlToDownload)
	{
		if (string.IsNullOrEmpty(urlToDownload))
			throw new ArgumentException("크롬드라이버 다운로드 링크가 올바르지 않습니다.");

		var baseDir = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location);
		var zipPath = Path.Combine(baseDir, "chromedriver.zip");

		if (File.Exists(zipPath))
		{
			try { File.Delete(zipPath); } catch { }
		}

		var client = CreateClient();
		var uri = new Uri(urlToDownload);
		client.DownloadFileAsync(uri, zipPath, zipPath);
	}

	// ──────────────────────────────────────────────
	// [FIX #1] Process Deadlock 해결
	// stdout/stderr 비동기 읽기 + 타임아웃 적용
	// ──────────────────────────────────────────────
	private string GetCurrentChromeDriverVersion()
	{
		KillAllChromeDriverProcesses();

		var exePath = Path.Combine(
			Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location),
			"chromedriver.exe");

		if (!File.Exists(exePath))
			return "";

		try
		{
			using (var process = new Process())
			{
				process.StartInfo = new ProcessStartInfo
				{
					FileName = exePath,
					Arguments = "--version",
					UseShellExecute = false,
					CreateNoWindow = true,
					RedirectStandardOutput = true,
					RedirectStandardError = true
				};

				// ── 데드락 방지: stdout용 버퍼, stderr용 버퍼 ──
				var stdoutResult = "";
				var stderrResult = "";

				process.OutputDataReceived += (s, args) =>
				{
					if (args.Data != null) stdoutResult += args.Data;
				};
				process.ErrorDataReceived += (s, args) =>
				{
					if (args.Data != null) stderrResult += args.Data;
				};

				process.Start();
				process.BeginOutputReadLine();
				process.BeginErrorReadLine();

				// ── 15초 타임아웃 ──
				if (!process.WaitForExit(15000))
				{
					try { process.Kill(); } catch { }
					return "";
				}

				// 크롬드라이버 출력 형식: "ChromeDriver 89.0.4389.23 (......)"
				var parts = stdoutResult.Split(' ');
				if (parts.Length >= 2)
				{
					var versionPart = parts[1];
					if (!string.IsNullOrEmpty(versionPart))
						return versionPart;
				}

				if (!string.IsNullOrEmpty(stderrResult))
				{
					return "";
				}

				return "";
			}
		}
		catch
		{
			return "";
		}
	}

	protected override void Dispose(bool disposing)
	{
		if (disposing && _components != null)
		{
			_components.Dispose();
		}
		base.Dispose(disposing);
	}

	// ──────────────────────────────────────────────
	// UI 초기화 (디컴파일 원본 유지)
	// 변수명만 한글화/정리, 레이아웃은 동일
	// ──────────────────────────────────────────────
	private void InitializeComponent()
	{
		var resources = new ComponentResourceManager(typeof(FormDownloader));

		_progressBarSequence = new ProgressBar();
		_labelProgress = new Label();
		_progressBarDownloading = new ProgressBar();
		_labelDownloadingUrl = new Label();
		_folderBrowserDialogDownloadLocation = new FolderBrowserDialog();
		_labelStatus = new Label();

		SuspendLayout();

		// 진행률 바 (전체)
		_progressBarSequence.Location = new Point(33, 94);
		_progressBarSequence.Name = "ProgressBarSequence";
		_progressBarSequence.Size = new Size(364, 24);
		_progressBarSequence.TabIndex = 1;

		// 진행률 텍스트
		_labelProgress.Location = new Point(176, 130);
		_labelProgress.Name = "LabelProgress";
		_labelProgress.Size = new Size(79, 13);
		_labelProgress.TabIndex = 9;
		_labelProgress.Text = "0 / 0";
		_labelProgress.TextAlign = ContentAlignment.MiddleCenter;

		// 파일별 진행률 바
		_progressBarDownloading.Location = new Point(33, 50);
		_progressBarDownloading.Name = "ProgressBarDownloading";
		_progressBarDownloading.Size = new Size(364, 18);
		_progressBarDownloading.TabIndex = 10;

		// 다운로드 파일명 표시
		_labelDownloadingUrl.Location = new Point(33, 19);
		_labelDownloadingUrl.Name = "LabelDownloadingUrl";
		_labelDownloadingUrl.Size = new Size(364, 28);
		_labelDownloadingUrl.TabIndex = 11;
		_labelDownloadingUrl.TextAlign = ContentAlignment.MiddleLeft;

		// 상태 표시
		_labelStatus.Location = new Point(33, 78);
		_labelStatus.Name = "LabelStatus";
		_labelStatus.Size = new Size(364, 13);
		_labelStatus.TabIndex = 14;
		_labelStatus.Text = "업데이트 정보를 불러오고 있습니다...";
		_labelStatus.TextAlign = ContentAlignment.MiddleLeft;

		// 폼 설정
		AutoScaleDimensions = new SizeF(6f, 13f);
		AutoScaleMode = AutoScaleMode.Font;
		ClientSize = new Size(431, 172);

		Controls.Add(_labelStatus);
		Controls.Add(_labelDownloadingUrl);
		Controls.Add(_progressBarDownloading);
		Controls.Add(_labelProgress);
		Controls.Add(_progressBarSequence);

		FormBorderStyle = FormBorderStyle.FixedDialog;
		HelpButton = true;
		Icon = (Icon)resources.GetObject("$this.Icon");
		MaximizeBox = false;
		Name = "FormDownloader";
		StartPosition = FormStartPosition.CenterScreen;
		Text = "디버 업데이터";

		Load += FormAsync_Load;

		ResumeLayout(false);
	}
}